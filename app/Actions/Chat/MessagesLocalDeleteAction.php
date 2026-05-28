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

use App\Models\HiddenMessage;
use Illuminate\Database\Eloquent\Collection;

class MessagesLocalDeleteAction
{
	private Collection $messagesList;

	public function __construct(Collection $messagesList) {
		$this->messagesList = $messagesList;
	}

	public function execute()
	{
		HiddenMessage::insert($this->messagesList->map(function($item) {
			return [
				'message_id' => $item->id,
				'chat_id' => $item->chat_id,
				'user_id' => me()->id
			];
		})->toArray());
	}
}
