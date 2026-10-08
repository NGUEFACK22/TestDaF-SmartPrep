<?php

namespace Tests\Feature;

use App\Services\AI\GeminiService;
use App\Services\AI\MistralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * MistralService — fournisseur IA actif (AI_PROVIDER=mistral).
 * Tout en HTTP simulé : aucun appel réel, aucun quota consommé.
 */
class MistralServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'testdaf.ai.enabled' => true,
            'testdaf.ai.provider' => 'mistral',
            'testdaf.ai.mistral_api_key' => 'cle-test',
            'testdaf.ai.mistral_model' => 'mistral-small-latest',
            'testdaf.ai.mistral_endpoint' => 'https://api.mistral.ai/v1',
        ]);
    }

    private function qcmPayload(): array
    {
        $items = [];
        for ($i = 0; $i < 20; $i++) {
            $items[] = [
                'stimulus' => "Stimulus {$i}",
                'prompt' => "Question {$i} ?",
                'options' => ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'],
                'correct' => 'a',
                'explanation' => 'Parce que.',
                'difficulty' => 'C1',
            ];
        }

        return $items;
    }

    public function test_provider_binding_injects_mistral_when_selected(): void
    {
        $svc = app(GeminiService::class);

        $this->assertSame(MistralService::class, get_class($svc));
        $this->assertTrue($svc->enabled());
    }

    public function test_gemini_binding_when_provider_is_gemini(): void
    {
        config(['testdaf.ai.provider' => 'gemini']);

        $this->assertSame(GeminiService::class, get_class(app(GeminiService::class)));
    }

    public function test_generate_json_parses_mistral_response(): void
    {
        Http::fake([
            'api.mistral.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($this->qcmPayload())]],
                ],
            ], 200),
        ]);

        $result = app(MistralService::class)->generateJson('prompt', 'system');

        $this->assertIsArray($result);
        $this->assertCount(20, $result);
        $this->assertSame('Question 0 ?', $result[0]['prompt']);
        $this->assertNull(app(MistralService::class)->lastError());
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer cle-test')
            && $request['model'] === 'mistral-small-latest'
            && $request['response_format']['type'] === 'json_object');
    }

    public function test_rate_limit_is_reported_retryable(): void
    {
        Http::fake([
            'api.mistral.ai/*' => Http::response(['message' => 'Rate limit exceeded'], 429),
        ]);

        $svc = app(MistralService::class);
        $this->assertNull($svc->generateJson('prompt'));
        $this->assertTrue($svc->lastRetryable());
        $this->assertSame(429, $svc->lastError()['status']);
    }

    public function test_disabled_without_key(): void
    {
        config(['testdaf.ai.mistral_api_key' => '']);

        $this->assertFalse(app(MistralService::class)->enabled());
    }

    public function test_challenge_generation_uses_mistral_end_to_end(): void
    {
        Http::fake([
            'api.mistral.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($this->qcmPayload())]],
                ],
            ], 200),
        ]);

        $role = \App\Models\Role::where('slug', 'candidate')->first()
            ?? \App\Models\Role::create(['name' => 'C', 'slug' => 'candidate']);
        $user = \App\Models\User::factory()->create(['role_id' => $role->id]);

        $challenge = \App\Models\AiChallenge::create([
            'user_id' => $user->id,
            'skill' => 'lesen',
            'status' => 'generating',
            'level' => 'C1',
            'weak_snapshot' => [],
            'requested_at' => now(),
        ]);

        app(\App\Services\Challenge\ChallengeService::class)->generate($challenge);

        $challenge = $challenge->fresh();
        $this->assertSame('ready', $challenge->status);
        $count = $challenge->generatedTest->sections->flatMap(fn ($s) => $s->exercises)
            ->flatMap(fn ($e) => $e->questions)->count();
        $this->assertGreaterThanOrEqual(20, $count);
    }
}
