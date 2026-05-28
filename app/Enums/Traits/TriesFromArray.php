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

namespace App\Enums\Traits;

trait TriesFromArray
{
	public static function tryFromArray(array $values)
	{
		$types = array_map(function($value) {
			return self::tryFrom($value);
		}, $values);

		return array_filter($types, function($value) {
			return (! empty($value));
		});
	}
}
