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

namespace App\Enums\Media;

enum MediaStatus:string
{
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case UNPROCESSED = 'unprocessed';
    case FAILED = 'failed';

    public function isProcessed():bool
    {
        return $this == self::PROCESSED;
    }

    public function isProcessing():bool
    {
        return $this == self::PROCESSING;
    }
    
    
}
