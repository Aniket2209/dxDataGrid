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
    private function applyFilter($query, $filter)
    {
        if (!is_array($filter)) return $query;

        if (count($filter) === 3 && is_string($filter[0])) {
            list($field, $operator, $value) = $filter;

            switch ($operator) {
                case '=':
                    $query->where($field, $value);
                    break;
                case '<>':
                    $query->where($field, '!=', $value);
                    break;
                case 'contains':
                    $query->where($field, 'like', '%' . $value . '%');
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $query->where($field, $operator, $value);
                    break;
            }
        } elseif (strtoupper($filter[1] ?? '') === 'AND' || strtoupper($filter[1] ?? '') === 'OR') {
            $logic = strtoupper($filter[1]);
            $filters = $filter[0];
            if ($logic === 'AND') {
                foreach ($filters as $f) {
                    $query = $this->applyFilter($query, $f);
                }
            } elseif ($logic === 'OR') {
                $query->where(function ($q) use ($filters) {
                    foreach ($filters as $f) {
                        $q->orWhere(function ($q2) use ($f) {
                            $this->applyFilter($q2, $f);
                        });
                    }
                });
            }
        }

        return $query;
    }
}