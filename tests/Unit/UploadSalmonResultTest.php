<?php

namespace Tests\Unit;

use Tests\TestCase;

use Swaggest\JsonSchema\Schema;

class UploadSalmonResultTest extends TestCase
{
    private function getTestUser()
    {
        return factory(\App\User::class)->create();
    }

    private function getTestUserRequest($testUser = null)
    {
        return $this->actingAs(
            $testUser ? $testUser : $this->getTestUser(),
            'api'
        );
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
            $data->results = [$salmon_result];

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
        $testUser = $this->getTestUser();
        $testUserRequest = $this->getTestUserRequest($testUser);

        $splatnetJson = json_decode(
            file_get_contents($this->resultJsonPathProvider()[0][0]),
            true
        );

        $splatnetJson['play_time'] = \Carbon\Carbon::now()->timestamp;
        $splatnetJson['my_result']['pid'] = $testUser->player_id;

        $payload = ['results' => [$splatnetJson]];

        $successfulResponse = $testUserRequest->postJson('/api/results', $payload);
        $successfulResponse
            ->assertStatus(200)
            ->assertJson([
                [
                    'created' => true,
                ],
            ]);

        $this->assertEquals(
            $splatnetJson['my_result']['pid'],
            $testUser->player_id,
            '$testUser->player_id should be updated',
        );

        // Uploading same result twice should be impossible
        $failedResponse = $testUserRequest->postJson('/api/results', $payload);
        $failedResponse->assertStatus(200)
            ->assertJson([
                [
                    'created' => false,
                ],
            ]);

        // Once associated with player_id, you cannot upload result with different player_id
        // Note that acutual player_id is always [a-f0-9]{16}
        $payload2 = $payload;
        $payload2['results'][0]['my_result']['pid'] = 'non-associated';
        $failedResponse2 = $testUserRequest->postJson('/api/results', $payload2);
        $failedResponse2->assertStatus(403);

        // Only associated user can upload
        $payload3 = $payload;
        $anotherUser = $this->getTestUserRequest();
        $payload3['results'][0]['play_time'] += 100;
        $failedResponse3 = $anotherUser->postJson('/api/results', $payload3);
        $failedResponse3->assertStatus(403);
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