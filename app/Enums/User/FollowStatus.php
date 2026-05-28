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

enum FollowStatus: string
{
	case REQUESTED = 'requested';
	case FOLLOWING = 'following';
	case REJECTED = 'rejected';
	case BLOCKED = 'blocked';

	public function isRequested(): bool
	{
		return $this === self::REQUESTED;
	}

	public function isFollowing(): bool
	{
		return $this === self::FOLLOWING;
	}
}
