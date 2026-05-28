<?php

namespace App\Enums\AI;

enum ImageProvider: string
{
    case PEXELS = 'pexels';
    case UNSPLASH = 'unsplash';
    case PIXABAY = 'pixabay';
    case AI_GENERATED = 'ai_generated';

    public function label(): string
    {
        return match ($this) {
            self::PEXELS => 'Pexels',
            self::UNSPLASH => 'Unsplash',
            self::PIXABAY => 'Pixabay',
            self::AI_GENERATED => 'AI Generated',
        };
    }

    public function isEnabled(): bool
    {
        if ($this === self::AI_GENERATED) {
            // AI Generated is enabled if any AI provider is enabled
            return !empty(AIProvider::getEnabled());
        }

        $config = config("image-providers.providers.{$this->value}");
        return $config['enabled'] ?? false;
    }

    public function getConfig(): array
    {
        return config("image-providers.providers.{$this->value}", []);
    }

    public static function getEnabled(): array
    {
        return array_filter(self::cases(), fn($provider) => $provider->isEnabled());
    }

    public static function getDefault(): ?self
    {
        $default = config('image-providers.default');
        return self::tryFrom($default);
    }
}
