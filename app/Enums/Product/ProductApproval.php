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

enum ProductApproval: string
{
	case APPROVED = 'approved';
	case REJECTED = 'rejected';
	case PENDING = 'pending';

	public function label(): string
	{
		return match ($this) {
			self::APPROVED => __('labels.approval_labels.approved'),
			self::REJECTED => __('labels.approval_labels.rejected'),
			self::PENDING => __('labels.approval_labels.pending'),
		};
	}

	public function emoji(): string
	{
		return match ($this) {
			self::APPROVED => '✅',
			self::REJECTED => '❌',
			self::PENDING => '⏳',
		};
	}

	public function isApproved(): bool
	{
		return $this === self::APPROVED;
	}

	public function isRejected(): bool
	{
		return $this === self::REJECTED;
	}

	public function isPending(): bool
	{
		return $this === self::PENDING;
	}
}
