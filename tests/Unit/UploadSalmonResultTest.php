<?php

namespace Tests\Unit;

use Tests\TestCase;
// use Illuminate\Foundation\Testing\WithFaker;
// use Illuminate\Foundation\Testing\RefreshDatabase;

use Swaggest\JsonSchema\Schema;
use App\Http\Controllers\SalmonResultController;

class UploadSalmonResultTest extends TestCase
{
    public $schema;

    /**
     * @dataProvider resultJsonPathProvider
     * @doesNotPerformAssertions
     */
    public function testJsonSchema($path)
    {
        // TODO: Figure out how to save disk access
        $schema = Schema::import(json_decode(
            file_get_contents("schemas/upload-salmon-result.json")
        ));

        try {
            $salmon_result = json_decode(file_get_contents($path));
            $data = new \stdClass();
            $data->splatnet_json = $salmon_result;

            $schema->in($data);
        }
        catch (Swaggest\JsonSchema\Exception $e) {
            $this->fail("$path should be valid salmon result.");
        }
    }

    public function testUploadSalmonResult()
    {
        $parameters = [
            'splatnet_json' => json_decode(file_get_contents($this->resultJsonPathProvider()[0][0]), true),
        ];
        $parameters['splatnet_json']['play_time'] = \Carbon\Carbon::parse();
        $request = \Illuminate\Http\Request::create('/api/upload-salmon-result', 'POST', $parameters);
        $controller = new SalmonResultController;

        $successResponse = $controller->store($request, 1);
        $this->assertEquals(200, $successResponse->status());

        $errorResponse = $controller->store($request, 1);
        // Uploading same result twice is impossible
        $this->assertEquals(409, $errorResponse->status());
    }

    public function resultJsonPathProvider()
    {
        return array_map(
            function ($path) {
                return [$path];
            },
            glob(__DIR__ . '/salmon-results/*.json')
        );
    }
}
