<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;

class SalmonSearchController extends Controller
{
    public function player(Request $request)
    {
        $query = '%' . Helper::escapeLike($request->query('name')) . '%';

        return \App\SalmonPlayerName::where('name', 'LIKE', $query)
            ->limit(25)
            ->orderBy('updated_at', 'desc')
            ->get();
    }
}
