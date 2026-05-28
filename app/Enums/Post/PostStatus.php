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

namespace App\Enums\Post;

enum PostStatus:string
{
    case ACTIVE = 'active';
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case DELETED = 'deleted';
    case PROCESSING_VIDEO = 'processing_video';
}
