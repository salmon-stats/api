<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use GuzzleHttp\Client;
use function GuzzleHttp\json_decode;
use Mockery\Exception\RuntimeException;

class CreateSalmonWeaponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Schema::create('salmon_weapons', function (Blueprint $table) {
                // Use negative integer for Grizzco weapons and random weapons
                $table->smallInteger('id');
                $table->statInkKey('key', 32);

                $table->primary('id');
            });

            $client = new Client();
            $result = $client->get('https://stat.ink/api/v2/weapon');
            if ($result->getStatusCode() == 200) {
                $weapons = json_decode($result->getBody());
            } else {
                throw new RuntimeException("Stat.ink API is unavailable.");
            }

            // Filter weapon variants (e.g. Tentatek Splattershot to Splattershot)
            $weapons = array_filter($weapons, function ($weapon) {
                return
                    // Do not filter splatscope and liter4k_scope
                    $weapon->{'key'} === 'splatscope' ||
                    $weapon->{'key'} === 'liter4k_scope' ||
                    $weapon->{'main_ref'} === $weapon->{'key'};
            });

            array_push(
                $weapons,
                (object)['key' => 'kuma_blaster', 'splatnet' => 20000],
                (object)['key' => 'kuma_brella',  'splatnet' => 20010],
                (object)['key' => 'kuma_charger', 'splatnet' => 20020],
                (object)['key' => 'kuma_slosher', 'splatnet' => 20030],
                (object)['key' => '_random_green', 'splatnet' => -1],
                (object)['key' => '_random_gold', 'splatnet' => -2],
            );

            foreach ($weapons as $weapon) {
                DB::table('salmon_weapons')->insert([
                    'id' => $weapon->{'splatnet'},
                    'key' => $weapon->{'key'},
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
        Schema::dropIfExists('salmon_weapons');
    }
}
