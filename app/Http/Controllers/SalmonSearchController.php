<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;

class SalmonSearchController extends Controller
{
    public function player(Request $request)
    {
        $screenNameQuery = Helper::escapeLike($request->query('name')) . '%';
        $nameQuery = '%' . $screenNameQuery;

        $names = \App\SalmonPlayerName::where('name', 'LIKE', $nameQuery)
            ->limit(25)
            ->orderBy('updated_at', 'desc')
            ->get();


        $registeredUsers = \App\User::where('name', 'LIKE', $screenNameQuery)
            ->whereNotNull('player_id')
            ->select(\App\User::getRawSelectQuery())
            ->limit(25)
            ->orderBy('updated_at', 'desc')
            ->get();

        return [
            'names' => $names,
            'users' => $registeredUsers,
        ];
    }
}
