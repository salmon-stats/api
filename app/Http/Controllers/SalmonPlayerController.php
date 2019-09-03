<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\SalmonPlayerRepository;

class SalmonPlayerController extends Controller
{
    private $repository;

    public function __construct(SalmonPlayerRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        return $this->repository->get($request->player_id);
    }
}
