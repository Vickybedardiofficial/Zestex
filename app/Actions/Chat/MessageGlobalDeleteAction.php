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

namespace App\Actions\Chat;

use App\Models\Message;

class MessageGlobalDeleteAction
{
	private Message $messageData;

	public function __construct(Message $messageData) {
		$this->messageData = $messageData;
	}

	public function execute()
	{
		$this->messageData->reactions()->delete();

		$this->messageData->linkSnapshot()->delete();

		$this->messageData->update([
			'content' => '',
			'is_deleted' => true
		]);
	}
}
