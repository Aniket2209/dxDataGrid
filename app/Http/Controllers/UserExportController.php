<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\UsersExport;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\User;
class UserExportController extends Controller
{
    public function export(Request $request)
    {
        return Excel::download(new UsersExport, 'users.csv');
    }
    public function exportCsvWithFastExcel()
    {
        $generator = function () {
            foreach (User::cursor() as $user) {
                yield $user;
            }
        };

        return (new FastExcel($generator()))->download('users.csv', function ($user) {
            return [
                'ID'                => $user->id,
                'Name'              => $user->name,
                'Email'             => $user->email,
                'Email Verified At' => $user->email_verified_at,
                'Created At'        => $user->created_at,
            ];
        });
    }
    public function usersWithPostsGenerator()
    {
        foreach (\App\Models\User::with('posts')->cursor() as $user) {
            yield $user;
        }
    }
    public function exportUsersWithPostsCsv()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(600);
        $generator = $this->usersWithPostsGenerator();

        return (new FastExcel($generator))->download('users_with_posts.csv', function ($user) {
            return [
                'ID'            => $user->id,
                'Name'          => $user->name,
                'Email'         => $user->email,
                'Posts Titles'  => $user->posts->pluck('title')->implode('; '),  // combine titles separated by semicolon
                'Posts Count'   => $user->posts->count(),
                'Created At'    => $user->created_at,
            ];
        });
    }
}
