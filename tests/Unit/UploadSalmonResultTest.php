<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Swaggest\JsonSchema\Schema;

class UploadSalmonResultTest extends TestCase
{
    use RefreshDatabase;

    private function getTestUser()
    {
        return factory(\App\User::class)->create();
    }

    private function getTestUserRequest()
    {
        return $this->actingAs($this->getTestUser(), 'api');
    }

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

    public function testUploadRequiresAuth()
    {
        $response = $this->postJson('/api/results');
        $response->assertStatus(401);
    }

    public function testEmptyRequestIsInvalid()
    {
        $response = $this->getTestUserRequest()->postJson('/api/results', []);
        $response->assertStatus(400);
    }

    public function testUploadSalmonResult()
    {
        $payload = [
            'splatnet_json' => json_decode(
                file_get_contents($this->resultJsonPathProvider()[0][0]),
                true
            ),
        ];
        $payload['splatnet_json']['play_time'] = \Carbon\Carbon::now()->timestamp;

        $testUserRequest = $this->getTestUserRequest();
        $successfulResponse = $testUserRequest->postJson('/api/results', $payload);
        $successfulResponse
            ->assertStatus(200)
            ->assertJsonStructure(['salmon_result_id']);

        // Uploading same result twice should be impossible
        $failedResponse = $testUserRequest->postJson('/api/results', $payload);
        $failedResponse->assertStatus(409);
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
