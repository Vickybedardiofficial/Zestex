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

namespace App\Actions\Post;

use App\Models\Post;
use App\Actions\Media\DeleteMediaAction;
use App\Actions\Comment\DeleteCommentAction;

class DeletePostAction
{
	private $postData;

	public function __construct(Post $postData)
	{
		$this->postData = $postData;
	}

	public function execute()
	{
		$this->postData->media->each(function ($mediaItem) {
			(new DeleteMediaAction($mediaItem))->execute();
		});

		$quotingPost = $this->postData->quotingPost;

		if($quotingPost) {
			$quotingPost->update([
				'quote_post_id' => null,
			]);
		}

		// TODO: Delete all quotes of the post, and polls and other related data.

		$this->postData->linkSnapshot()->delete();
		$this->postData->reports()->delete();
		$this->postData->poll()->delete();
		$this->postData->reactions()->delete();

		$this->postData->comments()->chunk(500, function ($comments) {
			foreach ($comments as $comment) {
				(new DeleteCommentAction($comment))->execute();
			}
		});

		$this->postData->delete();
	}
}