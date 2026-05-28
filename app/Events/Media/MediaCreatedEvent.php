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

namespace App\Events\Media;

use Illuminate\Foundation\Events\Dispatchable;

class MediaCreatedEvent
{
    use Dispatchable;

    public $mediaItem;

    /**
     * Create a new event instance.
     */
    public function __construct($mediaItem)
    {
        $this->mediaItem = $mediaItem;
    }
}
