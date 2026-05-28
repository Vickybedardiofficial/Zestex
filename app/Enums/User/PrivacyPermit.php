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

namespace App\Enums\User;

enum PrivacyPermit: string
{
	case ALL = 'all';
	case FOLLOWERS = 'followers';
	case NOBODY = 'nobody';
	case APPROVED = 'approved';

	public function nobody(): bool
	{
		return $this === self::NOBODY;
	}

	public static function followPermits(): array
	{
		return [
			self::ALL,
			self::APPROVED
		];
	}

	public function onlyApproved()
	{
		return $this === self::APPROVED;
	}
}