<?php

namespace Tests\Unit;

use Illuminate\Support\Str;
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

        $pid1 = Str::random(16);
        $splatnetJson['play_time'] = \Carbon\Carbon::now()->timestamp;
        $splatnetJson['my_result']['pid'] = $pid1;

        $payload = ['results' => [$splatnetJson]];

        $successfulResponse = $testUserRequest->postJson('/api/results', $payload);
        $successfulResponse
            ->assertStatus(200)
            ->assertJson([
                [
                    'created' => true,
                ],
            ]);

        $this->assertTrue(
            \App\UserAccount::where('player_id', $pid1)
                ->where('is_primary', true)
                ->exists(),
            'primary account should be created.',
        );

        // Uploading same result twice should be impossible
        $failedResponse = $testUserRequest->postJson('/api/results', $payload);
        $failedResponse->assertStatus(200)
            ->assertJson([
                [
                    'created' => false,
                ],
            ]);

        // Uploading different player's result creates new non-primary account.
        $payload2 = $payload;
        $pid2 = Str::random(16);
        $payload2['results'][0]['my_result']['pid'] = $pid2;
        $failedResponse2 = $testUserRequest->postJson('/api/results', $payload2);
        $failedResponse2->assertStatus(200);

        $this->assertTrue(
            \App\UserAccount::where('player_id', $pid2)
                ->where('is_primary', false)
                ->exists(),
            'non-primary account should be created.',
        );

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
