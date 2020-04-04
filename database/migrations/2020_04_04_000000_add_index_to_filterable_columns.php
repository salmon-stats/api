<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToFilterableColumns extends Migration
{
    private $srColumnsToAddIndex = ['boss_elimination_count', 'golden_egg_delivered', 'power_egg_collected'];
    private $sprColumnsToAddIndex = ['boss_elimination_count', 'golden_eggs', 'power_eggs'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salmon_results', function (Blueprint $table) {
            foreach ($this->srColumnsToAddIndex as $column) {
                $table->index($column);
            }
        });

        Schema::table('salmon_player_results', function (Blueprint $table) {
            foreach ($this->sprColumnsToAddIndex as $column) {
                $table->index($column);
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
        Schema::table('salmon_results', function (Blueprint $table) {
            $table->dropIndex($this->srColumnsToAddIndex);
        });

        Schema::table('salmon_player_results', function (Blueprint $table) {
            $table->dropIndex($this->sprColumnsToAddIndex);
        });
    }
}
