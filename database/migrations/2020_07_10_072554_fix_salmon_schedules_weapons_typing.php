<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixSalmonSchedulesWeaponsTyping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $schedules = \App\SalmonSchedule::all();

            foreach ($schedules as $schedule) {
                $schedule->weapons = array_map(fn ($weapon) => (int) $weapon, $schedule->weapons);
                $schedule->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration is irreversible.
    }
}
