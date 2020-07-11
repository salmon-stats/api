<?php

namespace App\Http\Controllers;

use App\Helpers\SalmonResultsFilterHelper;
use Illuminate\Http\Request;
use App\Repositories\SalmonScheduleRepository;
use App\SalmonSchedule;

class SalmonScheduleController extends Controller
{
    private $repository;

    public function __construct(SalmonScheduleRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $results = new SalmonSchedule();

        return SalmonResultsFilterHelper::apply($results, $request->all())
            ->orderBy('schedule_id', 'desc')
            ->paginate(15);
    }

    public function show(Request $request)
    {
        $isRequestingResults = $request->route()->getName() === 'schedules.results';
        return $this->repository->get($request->schedule_id, $isRequestingResults);
    }
}
