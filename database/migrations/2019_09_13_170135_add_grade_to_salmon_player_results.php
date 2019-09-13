<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGradeToSalmonPlayerResults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salmon_player_results', function (Blueprint $table) {
            $table->unsignedSmallInteger('grade_point')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salmon_player_results', function (Blueprint $table) {
            $table->dropColumn('grade_point');
        });
    }
}
