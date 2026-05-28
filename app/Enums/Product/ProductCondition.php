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

use App\Enums\Traits\TriesFromArray;

enum ProductCondition: string
{
	use TriesFromArray;
	
	case USED = 'used';
	case NEW = 'new';
	case ACCEPTABLE = 'acceptable';
	case REFURBISHED = 'refurbished'; 
	case DEFECTIVE = 'defective';

	public function label()
	{
		return match ($this) {
			self::USED => __('labels.condition_labels.used'),
			self::NEW => __('labels.condition_labels.new'),
			self::ACCEPTABLE => __('labels.condition_labels.acceptable'),
			self::REFURBISHED => __('labels.condition_labels.refurbished'),
			self::DEFECTIVE => __('labels.condition_labels.defective'),
		};
	}

	public function emoji(): string
	{
		return match ($this) {
			self::USED => '🔥',
			self::NEW => '🆕',
			self::ACCEPTABLE => '👌',
			self::REFURBISHED => '🔄',
			self::DEFECTIVE => '❌',
		};
	}

	public static function physicalProductConditions()
	{
		return [
			[
				'key' => self::USED->value,
				'value' => __('labels.condition_labels.used')
			],
			[
				'key' => self::NEW->value,
				'value' => __('labels.condition_labels.new')
			],
			[
				'key' => self::ACCEPTABLE->value,
				'value' => __('labels.condition_labels.acceptable')
			],
			[
				'key' => self::REFURBISHED->value,
				'value' => __('labels.condition_labels.refurbished')
			],
			[
				'key' => self::DEFECTIVE->value,
				'value' => __('labels.condition_labels.defective')
			],
		];
	}

	public static function digitalProductConditions()
	{
		return [
			[
				'key' => self::NEW->value,
				'value' => __('labels.condition_labels.new')
			]
		];
	}
}
