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
        try {
            if ($request->filled('filter')) {
                $filter = json_decode($request->input('filter'), true);
                \Log::info('Filters received:', ['filter' => $filter]);
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
        catch (\Exception $e) {
            \Log::error('usersJson error:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Failed to load data: ' . $e->getMessage()], 500);
        }
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
        $dateFields = ['email_verified_at', 'created_at'];

        if (!is_array($filter)) return $query;

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

            // Date parsing helper
            $parseDate = function($dateStr) {
                if (empty($dateStr)) return null;

                try {
                    $dt = new \DateTime($dateStr);
                    return $dt->format('Y-m-d');
                } catch (\Exception $e) {
                    // fallback to d-m-Y format
                    $dt = \DateTime::createFromFormat('d-m-Y', $dateStr);
                    return $dt ? $dt->format('Y-m-d') : null;
                }
            };

            if (in_array($field, $dateFields)) {
                if (is_array($value)) {
                    $value = array_map($parseDate, $value);
                    $value = array_filter($value);

                    if (count($value) < 2) {
                        \Log::warning("applyFilter: Invalid date range filter for field '{$field}' - less than 2 valid dates.");
                        // Force empty result set to avoid returning all data
                        $query->whereRaw('0 = 1');
                        return $query;
                    }

                } else {
                    $value = $parseDate($value);
                    if ($value === null) {
                        \Log::warning("applyFilter: Invalid date filter value for field '{$field}'.");
                        // Force empty result set to avoid returning all data
                        $query->whereRaw('0 = 1');
                        return $query;
                    }
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
                    // Unknown operator, consider logging or throwing an error here if needed
                    \Log::warning("applyFilter: Unknown operator '{$operator}' for field '{$field}'.");
                    break;
            }
        }

        return $query;
    }
}