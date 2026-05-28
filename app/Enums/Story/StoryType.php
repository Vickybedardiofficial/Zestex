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

namespace App\Enums\Story;

enum StoryType: string
{
	case VIDEO = 'video';
	case IMAGE = 'image';
	case TEXT = 'text';

	public function isImage():bool
    {
        return $this == self::IMAGE;
    }

	public function isVideo():bool
    {
        return $this == self::VIDEO;
    }

	public function isTextified():bool
    {
        return $this == self::TEXT;
    }

	public function label():string
    {
        return match($this) {
            self::VIDEO => __('labels.media_type_labels.video'),
            self::IMAGE => __('labels.media_type_labels.image'),
            self::TEXT => __('labels.media_type_labels.text'),
        };
    }
}
