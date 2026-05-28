<?php

namespace App\Enums\AI;

enum AIProvider: string
{
    case XAI = 'xai';
    case GEMINI = 'gemini';
    case CHATGPT = 'chatgpt';
    case CLAUDE = 'claude';
    case GROQ = 'groq';
    case OPENROUTER = 'openrouter';
    case AIMLAPI = 'aimlapi';

    public function label(): string
    {
        return match ($this) {
            self::XAI => 'XAI (Grok)',
            self::GEMINI => 'Google Gemini',
            self::CHATGPT => 'OpenAI ChatGPT',
            self::CLAUDE => 'Anthropic Claude',
            self::GROQ => 'Groq',
            self::OPENROUTER => 'OpenRouter',
            self::AIMLAPI => 'AIMLAPI',
        };
    }

    public function isEnabled(): bool
    {
        $config = config("ai-providers.providers.{$this->value}");
        return $config['enabled'] ?? false;
    }

    public function getConfig(): array
    {
        return config("ai-providers.providers.{$this->value}", []);
    }

    public static function getEnabled(): array
    {
        return array_filter(self::cases(), fn($provider) => $provider->isEnabled());
    }

    public static function getDefault(): ?self
    {
        $default = config('ai-providers.default');
        return self::tryFrom($default);
    }
}
