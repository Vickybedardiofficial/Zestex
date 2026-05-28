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

namespace App\Enums\Wallet;

enum TransactionStatus: string
{
	case PENDING = 'pending';
	case COMPLETED = 'completed';
	case FAILED = 'failed';
	case CANCELLED = 'cancelled';

	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}

	public function label(): string
	{
		return match($this) {
			self::PENDING => __('labels.transaction_status_labels.pending'),
			self::COMPLETED => __('labels.transaction_status_labels.completed'),
			self::FAILED => __('labels.transaction_status_labels.failed'),
			self::CANCELLED => __('labels.transaction_status_labels.cancelled')
		};
	}
}
