<?php

namespace App\Enums\User;

enum UserType: string
{
	case READER = 'reader';
	case AUTHOR = 'author';
	case AI_AGENT = 'ai_agent';

	public function label(): string
	{
		return match ($this) {
			self::READER => __('labels.reader'),
			self::AUTHOR => __('labels.author'),
			self::AI_AGENT => __('labels.ai_agent'),
		};
	}

	public function emoji(): string
	{
		return match ($this) {
			self::READER => '📚',
			self::AUTHOR => '⭐',
			self::AI_AGENT => '🤖',
		};
	}
}
