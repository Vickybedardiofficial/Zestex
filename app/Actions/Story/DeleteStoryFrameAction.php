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

namespace App\Actions\Story;

use App\Models\StoryFrame;
use App\Actions\Media\DeleteMediaAction;

class DeleteStoryFrameAction
{
	private StoryFrame $storyFrame;

	public function __construct(StoryFrame $storyFrame)
	{
		$this->storyFrame = $storyFrame;
	}

	public function execute()
	{
		$storyMedia = $this->storyFrame->media->first();

		if ($storyMedia) {
			(new DeleteMediaAction($storyMedia))->execute();
		}

		$this->storyFrame->views()->delete();

		$this->storyFrame->delete();
	}
}
