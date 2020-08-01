<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsEligibleForNoNightRecordToSalmonResults extends Migration
{
    private $columToAdd = 'is_eligible_for_no_night_record';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salmon_results', function (Blueprint $table) {
            $table->boolean($this->columToAdd)->default(false);
        });

        DB::statement(<<<QUERY
        WITH no_night_ids AS (
            SELECT salmon_id, count(*) AS waves, sum(event_id) = 0 AS has_no_night
                FROM salmon_waves
                GROUP BY salmon_id
                HAVING waves = 3 AND has_no_night = true
        )
        UPDATE salmon_results
        SET $this->columToAdd = TRUE
        WHERE id in (SELECT salmon_id FROM no_night_ids)
        QUERY);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salmon_results', function (Blueprint $table) {
            $table->dropColumn($this->columToAdd);
        });
    }
}
