<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function usersJson(Request $request)
    {
        $query = User::query();

        // Sorting
        if ($request->has('sort')) {
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
}