<?php

namespace Tests\Unit;

use Tests\TestCase;
// use Illuminate\Foundation\Testing\WithFaker;
// use Illuminate\Foundation\Testing\RefreshDatabase;

use Swaggest\JsonSchema\Schema;
use App\Http\Controllers\SalmonResultController;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UploadSalmonResultTest extends TestCase
{
    /**
     * @dataProvider resultJsonPathProvider
     * @doesNotPerformAssertions
     */
    public function testJsonSchema($path)
    {
        // TODO: Figure out how to save disk access
        $schema = Schema::import(json_decode(
            file_get_contents('schemas/upload-salmon-result.json')
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

    /**
     * @dataProvider resultJsonPathProvider
     */
    public function testUploadSalmonResult($path)
    {
        $payload = [
            // 'splatnet_json' => json_decode(file_get_contents($this->resultJsonPathProvider()[0][0]), true),
            'splatnet_json' => json_decode(file_get_contents($path), true),
        ];
        $payload['splatnet_json']['play_time'] = \Carbon\Carbon::now()->timestamp;
        $emptyRequest = \Illuminate\Http\Request::create('/api/upload-salmon-result', 'POST');
        $validRequest = \Illuminate\Http\Request::create(
            '/api/upload-salmon-result',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );
        $controller = new SalmonResultController;

        $successResponse = $controller->store($validRequest, 1);
        $this->assertEquals(200, $successResponse->status());
        $this->assertObjectHasAttribute('salmon_result_id', $successResponse->getData());

        try {
            $controller->store($validRequest, 1);
            $this->fail('Uploading same result twice should throw exception');
        }
        catch (HttpException $e) {
            $this->assertEquals(409, $e->getStatusCode());
        }

        try {
            $controller->store($emptyRequest, 1);
            $this->fail('Uploading empty result should throw exception');
        }
        catch (HttpException $e) {
            $this->assertEquals(400, $e->getStatusCode());
        }
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
