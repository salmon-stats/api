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

                $table->foreign('stage_id')->references('id')->on('salmon_stages');
                // $table->foreign('rare_weapon_id')->references('id')->on('salmon_weapons');
            });

            $stageJapaneseNames = [
                'シェケナダム' => 1,
                '難破船ドン・ブラコ' => 2,
                '海上集落シャケト場' => 3,
                'トキシラズいぶし工房' => 4,
                '朽ちた箱舟 ポラリス' => 5,
            ];

            $client = new Client();
            $result = $client->get('https://spla2.yuu26.com/coop');
            if ($result->getStatusCode() == 200) {
                $schedules = json_decode($result->getBody())->result;
            } else {
                throw new RuntimeException("spla2.yuu26.com API is unavailable.");
            }

            foreach ($schedules as $schedule) {
                if (!$schedule->weapons) {
                    continue;
                }

                $weapons = array_map(function ($weapon) {
                    return $weapon->id;
                }, $schedule->weapons);
                $startTime = Carbon::parse($schedule->start_utc);
                $endTime = Carbon::parse($schedule->end_utc);

                DB::table('salmon_schedules')->insert([
                    'schedule_id' => $startTime,
                    'end_at' => $endTime,
                    'weapons' => json_encode($weapons),
                    'rare_weapon_id' => 20000,
                    'stage_id' => $stageJapaneseNames[$schedule->stage->name],
                ]);
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
