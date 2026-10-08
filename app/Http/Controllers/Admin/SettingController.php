<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'settings' => [
                'ai_enabled' => (bool) Setting::get('ai_enabled', config('testdaf.ai.enabled')),
                'ai_provider' => Setting::get('ai_provider', config('testdaf.ai.provider')),
                'ai_model' => Setting::get('ai_model', config('testdaf.ai.model')),
                'max_ai_requests' => (int) Setting::get('max_ai_requests', config('testdaf.ai.max_requests')),
                'ai_auto_evaluation' => (bool) Setting::get('ai_auto_evaluation', config('testdaf.ai.auto_evaluation')),
                'ai_manual_review' => (bool) Setting::get('ai_manual_review', config('testdaf.ai.manual_review')),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'ai_enabled' => ['sometimes', 'boolean'],
            'ai_provider' => ['required', 'string', 'in:gemini,mistral,groq'],
            'ai_model' => ['required', 'string', 'max:100'],
            'max_ai_requests' => ['required', 'integer', 'min:0', 'max:100000'],
            'ai_auto_evaluation' => ['sometimes', 'boolean'],
            'ai_manual_review' => ['sometimes', 'boolean'],
        ]);

        Setting::put('ai_enabled', $request->boolean('ai_enabled'), 'bool');
        Setting::put('ai_provider', $data['ai_provider']);
        Setting::put('ai_model', $data['ai_model']);
        Setting::put('max_ai_requests', $data['max_ai_requests'], 'int');
        Setting::put('ai_auto_evaluation', $request->boolean('ai_auto_evaluation'), 'bool');
        Setting::put('ai_manual_review', $request->boolean('ai_manual_review'), 'bool');

        return back()->with('status', 'Paramètres IA enregistrés.');
    }
}
