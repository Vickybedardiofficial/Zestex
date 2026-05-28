<?php
/*
|--------------------------------------------------------------------------
| Zestex - Social Network Platform.
|--------------------------------------------------------------------------
| Based on: Zestex - The Social Network Web Application.
|--------------------------------------------------------------------------
| Author: Vicky Bedardi Yadav
|--------------------------------------------------------------------------
| Branded by: Vicky Bedardi Yadav
| E-mail: vicktbedardi9@gmail.com
|--------------------------------------------------------------------------
| Copyright (c) Flip Basket Pvt Ltd. All rights reserved. 
|--------------------------------------------------------------------------
*/

namespace App\Enums\Category;

enum CategoryStatus: string
{
	case ACTIVE = 'active';
	case INACTIVE = 'inactive';
	case DRAFT = 'draft';

	public function isActive(): bool
	{
		return $this === self::ACTIVE;
	}

	public function isInactive(): bool
	{
		return $this === self::INACTIVE;
	}

	public function isDraft(): bool
	{
		return $this === self::DRAFT;
	}

	public function label(): string
	{
		return match ($this) {
			self::ACTIVE => __('labels.status_labels.active'),
			self::INACTIVE => __('labels.status_labels.inactive'),
			self::DRAFT => __('labels.status_labels.draft'),
		};
	}

	public function emoji(): string
	{
		return match ($this) {
			self::ACTIVE => '✅',
			self::INACTIVE => '❌',
			self::DRAFT => '🗒️',
		};
	}
}