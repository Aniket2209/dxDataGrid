<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function usersJson(Request $request)
    {
        $query = User::query();

        //filtering
        if ($request->filled('filter')) {
            $filter = json_decode($request->input('filter'), true);
            $query = $this->applyFilter($query, $filter);
        }
        // Sorting
        if ($request->filled('sort')) {
            $sorts = json_decode($request->input('sort'), true);
            if (is_array($sorts)) {
                foreach ($sorts as $sort) {
                    $query->orderBy($sort['selector'], $sort['desc'] ? 'desc' : 'asc');
                }
            }
        }

        // Paging parameters
        $skip = (int) $request->input('skip', 0);
        $take = (int) $request->input('take', 20);

        $total = $query->count();

        $users = $query->skip($skip)->take($take)->get();

        return response()->json([
            'data' => $users,
            'totalCount' => $total,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt('password'), // Set default password or handle accordingly
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user->update($data);
        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(null, 204);
    }

    private function applyFilter($query, $filter)
    {
        if (!is_array($filter)) {
            return $query;
        }

        if (isset($filter[1]) && (strtoupper($filter[1]) === 'AND' || strtoupper($filter[1]) === 'OR')) {
            $logic = strtolower($filter[1]);
            $left = $filter[0];
            $right = $filter[2];

            if ($logic === 'and') {
                $query->where(function ($q) use ($left, $right) {
                    $this->applyFilter($q, $left);
                    $this->applyFilter($q, $right);
                });
            } elseif ($logic === 'or') {
                $query->where(function ($q) use ($left, $right) {
                    $q->where(function ($q2) use ($left) {
                        $this->applyFilter($q2, $left);
                    })->orWhere(function ($q2) use ($right) {
                        $this->applyFilter($q2, $right);
                    });
                });
            }

            return $query;
        }

        if (count($filter) === 3) {
            [$field, $operator, $value] = $filter;

            $dateFields = ['email_verified_at', 'created_at'];
            if (in_array($field, $dateFields)) {
                if (is_array($value)) {
                    $value = array_map(fn($v) => date('Y-m-d', strtotime($v)), $value);
                } else {
                    $value = date('Y-m-d', strtotime($value));
                }
            }

            switch (strtolower($operator)) {
                case '=':
                    if (in_array($field, $dateFields)) {
                        $query->whereDate($field, $value);
                    } else {
                        $query->where($field, '=', $value);
                    }
                    break;
                case '<>':
                case '!=':
                    if (in_array($field, $dateFields)) {
                        $query->whereDate($field, '!=', $value);
                    } else {
                        $query->where($field, '!=', $value);
                    }
                    break;
                case 'contains':
                    $query->where($field, 'like', "%$value%");
                    break;
                case 'notcontains':
                    $query->where($field, 'not like', "%$value%");
                    break;
                case 'startswith':
                    $query->where($field, 'like', "$value%");
                    break;
                case 'endswith':
                    $query->where($field, 'like', "%$value");
                    break;
                case 'between':
                    if (is_array($value) && count($value) === 2) {
                        if (in_array($field, $dateFields)) {
                            $query->whereDate($field, '>=', $value[0])
                                ->whereDate($field, '<=', $value[1]);
                        } else {
                            $query->whereBetween($field, $value);
                        }
                    }
                    break;
                case 'notbetween':
                    if (is_array($value) && count($value) === 2) {
                        if (in_array($field, $dateFields)) {
                            $query->whereDate($field, '<', $value[0])
                                ->orWhereDate($field, '>', $value[1]);
                        } else {
                            $query->whereNotBetween($field, $value);
                        }
                    }
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    if (in_array($field, $dateFields)) {
                        $query->whereDate($field, $operator, $value);
                    } else {
                        $query->where($field, $operator, $value);
                    }
                    break;
                default:
                    break;
            }
        }

        return $query;
    }
}