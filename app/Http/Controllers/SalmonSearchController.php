<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;

class SalmonSearchController extends Controller
{
    public function player(Request $request)
    {
        $name = $request->query('name');
        if (empty($name)) {
            abort(400);
        }

        $screenNameQuery = Helper::escapeLike($name) . '%';
        $nameQuery = '%' . $screenNameQuery;

        $names = \App\SalmonPlayerName::where('name', 'LIKE', $nameQuery)
            ->limit(25)
            ->orderBy('updated_at', 'desc')
            ->get();

        $registeredUsers = \App\User::where(fn ($q) => $q
            ->where('name', 'LIKE', $screenNameQuery)
            ->orWhere('display_name', 'LIKE', $screenNameQuery)
        )
            ->whereNotNull('player_id')
            ->join('user_accounts', 'user_accounts.user_id', '=', 'users.id')
            ->limit(25)
            ->orderBy('updated_at', 'desc')
            ->get();

        return [
            'names' => $names,
            'users' => $registeredUsers,
        ];
    }
}
