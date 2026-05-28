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

namespace App\Actions\User;

class TerminateUserSessionsAction
{
	private $excludeCurrent = true;

	public function withCurrent()
	{
		$this->excludeCurrent = false;

		return $this;
	}

	public function execute()
	{
		me()->devices()->when($this->excludeCurrent, function ($query) { 
			return $query->where('session_id', '!=', session()->getId());
		})->update([
            'is_terminated' => true
        ]);
	}
}