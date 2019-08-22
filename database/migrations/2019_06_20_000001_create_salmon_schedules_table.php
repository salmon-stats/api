<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Carbon\Carbon;
use GuzzleHttp\Client;
use function GuzzleHttp\json_decode;

class CreateSalmonSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Schema::create('salmon_schedules', function (Blueprint $table) {
                $table->dateTime('schedule_id')->primary();
                $table->dateTime('end_at');
                $table->json('weapons'); // array of weapon_id
                $table->unsignedTinyInteger('stage_id');
                $table->unsignedSmallInteger('rare_weapon_id')->nullable();

                $table->foreign('stage_id')->references('id')->on('salmon_stages')->onDelete('cascade');
                // $table->foreign('rare_weapon_id')->references('id')->on('salmon_weapons')->onDelete('cascade');
            });

            $stageIdtable = [
                5000 => 1,
                5001 => 2,
                5002 => 3,
                5003 => 4,
                5004 => 5,
            ];

            $client = new Client();

            $result = $client->get('https://files.oatmealdome.me/bcat/coop.json');
            if ($result->getStatusCode() == 200) {
                $schedules = json_decode($result->getBody())->Phases;
            } else {
                throw new RuntimeException('oatmealdome.me API is unavailable.');
            }

            foreach ($schedules as $rawSchedule) {
                $startTime = Carbon::parse($rawSchedule->StartDateTime);
                $endTime = Carbon::parse($rawSchedule->EndDateTime);

                $schedule = new \App\SalmonSchedule([
                    'schedule_id' => $startTime,
                    'end_at' => $endTime,
                    'weapons' => json_encode($rawSchedule->WeaponSets),
                    'rare_weapon_id' => $rawSchedule->RareWeaponID,
                    'stage_id' => $stageIdtable[$rawSchedule->StageID],
                ]);
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
        Schema::dropIfExists('salmon_schedules');
    }
}
