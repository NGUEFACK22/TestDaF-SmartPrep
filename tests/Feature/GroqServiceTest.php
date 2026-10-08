<?php

namespace Tests\Feature;

use App\Services\AI\GeminiService;
use App\Services\AI\GroqService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * GroqService — fournisseur IA actif (AI_PROVIDER=groq).
 * Tout en HTTP simulé : aucun appel réel, aucun quota consommé.
 */
class GroqServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'testdaf.ai.enabled' => true,
            'testdaf.ai.provider' => 'groq',
            'testdaf.ai.groq_api_key' => 'cle-test',
            'testdaf.ai.groq_model' => 'openai/gpt-oss-20b',
            'testdaf.ai.groq_endpoint' => 'https://api.groq.com/openai/v1',
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

    private function groqResponse(array $items, int $status = 200): array
    {
        return [
            'id' => 'chatcmpl-test',
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => json_encode($items)]],
            ],
        ];
    }

    public function test_provider_binding_injects_groq_when_selected(): void
    {
        $svc = app(GeminiService::class);

        $this->assertSame(GroqService::class, get_class($svc));
        $this->assertTrue($svc->enabled());
    }

    public function test_generate_json_parses_groq_response(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response($this->groqResponse($this->qcmPayload()), 200),
        ]);

        $result = app(GroqService::class)->generateJson('prompt JSON', 'system JSON');

        $this->assertIsArray($result);
        $this->assertCount(20, $result);
        $this->assertSame('Question 0 ?', $result[0]['prompt']);
        $this->assertNull(app(GroqService::class)->lastError());
        Http::assertSent(fn ($request) => str_starts_with((string) $request['model'], 'openai/')
            && $request['response_format']['type'] === 'json_object'
            && $request->hasHeader('Authorization', 'Bearer cle-test'));
    }

    public function test_rate_limit_is_reported_retryable(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['message' => 'Rate limit exceeded'], 429),
        ]);

        $svc = app(GroqService::class);
        $this->assertNull($svc->generateJson('prompt JSON'));
        $this->assertTrue($svc->lastRetryable());
        $this->assertSame(429, $svc->lastError()['status']);
    }

    public function test_disabled_without_key(): void
    {
        config(['testdaf.ai.groq_api_key' => '']);

        $this->assertFalse(app(GroqService::class)->enabled());
    }

    public function test_challenge_generation_uses_groq_end_to_end(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response($this->groqResponse($this->qcmPayload()), 200),
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
