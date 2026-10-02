<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'ai_enabled' => [config('testdaf.ai.enabled') ? '1' : '0', 'bool'],
            'ai_provider' => [config('testdaf.ai.provider'), 'string'],
            'ai_model' => [config('testdaf.ai.model'), 'string'],
            'max_ai_requests' => [(string) config('testdaf.ai.max_requests'), 'int'],
            'ai_auto_evaluation' => [config('testdaf.ai.auto_evaluation') ? '1' : '0', 'bool'],
            'ai_manual_review' => [config('testdaf.ai.manual_review') ? '1' : '0', 'bool'],
        ];

        foreach ($defaults as $key => [$value, $type]) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => $type]);
        }
    }
}
