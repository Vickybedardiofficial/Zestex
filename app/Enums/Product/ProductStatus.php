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

namespace App\Enums\Product;

enum ProductStatus: string
{
	case DRAFT = 'draft';
	case ACTIVE = 'active';
	case INACTIVE = 'inactive';

	public function label(): string
	{
		return match ($this) {
			self::DRAFT => __('labels.status_labels.draft'),
			self::ACTIVE => __('labels.status_labels.active'),
			self::INACTIVE => __('labels.status_labels.inactive')
		};
	}

	public function emoji(): string
	{
		return match ($this) {
			self::DRAFT => '🗒️',
			self::ACTIVE => '🚀',
			self::INACTIVE => '❌',
		};
	}

	public function isActive(): bool
	{
		return $this === self::ACTIVE;
	}
}
