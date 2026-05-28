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

namespace App\Events\User\Story;

use App\Models\StoryFrame;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoryCreatedEvent
{
    use Dispatchable, SerializesModels;

    public $frameData;

    /**
     * Create a new event instance.
     */
    public function __construct(StoryFrame $frameData)
    {
        $this->frameData = $frameData;
    }
}
