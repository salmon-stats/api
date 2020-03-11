<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalmonStagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salmon_stages', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->statInkKey('key');
        });

        $keys = ['damu', 'donburako', 'shaketoba', 'tokishirazu', 'polaris'];
        foreach ($keys as $key) {
            DB::table('salmon_stages')->insert(['key' => $key]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salmon_stages');
    }
}
