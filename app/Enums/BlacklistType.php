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

namespace App\Enums;

enum BlacklistType: string
{
	case IP = 'ip_address';
	case EMAIL = 'email';
	case PHONE = 'phone';
	case USERNAME = 'username';

	public function label(): string
	{
		return match ($this) {
			self::IP => 'IP Address',
			self::EMAIL => 'Email',
			self::PHONE => 'Phone',
			self::USERNAME => 'Username',
		};
	}

	public function emoji(): string
	{
		return match ($this) {
			self::IP => '🌐',
			self::EMAIL => '📧',
			self::PHONE => '📱',
			self::USERNAME => '👤',
		};
	}
}
