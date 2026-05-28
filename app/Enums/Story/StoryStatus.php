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

enum StoryStatus: string
{
	case DRAFT = 'draft';
	case ACTIVE = 'active';
    case PROCESSING = 'processing';

	public function isDraft():bool
    {
        return $this == self::DRAFT;
    }

	public function isActive():bool
    {
        return $this == self::ACTIVE;
    }

    public function isProcessing():bool
    {
        return $this == self::PROCESSING;
    }
}
