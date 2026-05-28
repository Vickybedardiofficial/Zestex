<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\AdminSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;

class AdminSettingController extends Controller
{
    use SupportsApiResponses;

    public function index()
    {
        // Check admin permission (simplified for now)
        if (! me()->isAdmin()) {
            return $this->responseUnauthorizedError();
        }

        $settings = AdminSetting::whereIn('key', [
            'ai_active_provider',
            'ai_default_provider',
            'ai_fallback_providers',
            'ai_grok_model',
            'ai_grok_api_key',
            'ai_gemini_api_key',
            'ai_openai_api_key',
            'xai_api_key',
            'gemini_api_key',
            'openai_api_key',
            'claude_api_key',
            'groq_api_key',
            'openrouter_api_key',
            'aimlapi_api_key'
        ])->get()->mapWithKeys(function ($item) {
            return [$item->key => $item->value];
        });

        return $this->responseSuccess([
            'data' => $settings
        ]);
    }

    public function update(Request $request)
    {
        if (! me()->isAdmin()) {
            return $this->responseUnauthorizedError();
        }

        $data = $request->validate([
            'ai_active_provider' => 'nullable|string|in:xai,groq,gemini,chatgpt,claude,openrouter,aimlapi',
            'ai_default_provider' => 'nullable|string|in:xai,groq,gemini,chatgpt,claude,openrouter,aimlapi',
            'ai_fallback_providers' => 'nullable|string',
            'ai_grok_model'      => 'nullable|string',
            'ai_grok_api_key'    => 'nullable|string',
            'ai_gemini_api_key'  => 'nullable|string',
            'ai_openai_api_key'  => 'nullable|string',
            'xai_api_key'        => 'nullable|string',
            'gemini_api_key'     => 'nullable|string',
            'openai_api_key'     => 'nullable|string',
            'claude_api_key'     => 'nullable|string',
            'groq_api_key'       => 'nullable|string',
            'openrouter_api_key' => 'nullable|string',
            'aimlapi_api_key'    => 'nullable|string',
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                AdminSetting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'type' => 'string']
                );
            }
        }

        return $this->responseSuccess([
            'message' => 'AI Settings updated successfully.'
        ]);
    }
}
