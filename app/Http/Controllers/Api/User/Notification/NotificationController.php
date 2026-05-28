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

namespace App\Http\Controllers\Api\User\Notification;

use App\Support\Num;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Http\Resources\User\Notification\NotificationCollection;

class NotificationController extends Controller
{
    use SupportsApiResponses;

    public function getAll(Request $request)
    {
        $notifications = me()->notifications()->all()->get();
        
        $responseData = NotificationCollection::make($notifications);

        defer(function() use ($notifications) {
            $notifications->markAsRead();
        });

        return $this->responseSuccess([
            'data' => $responseData
        ]);
    }

    public function getMentions(Request $request)
    {
        $mentionNotifications = me()->notifications()->mentionable()->get();

        $responseData = NotificationCollection::make($mentionNotifications);

        defer(function() use ($mentionNotifications) {
            $mentionNotifications->markAsRead();
        });

        return $this->responseSuccess([
            'data' => $responseData
        ]);
    }

    public function getImportant(Request $request)
    {
        $importantNotifications = me()->notifications()->important()->get();

        $responseData = NotificationCollection::make($importantNotifications);

        defer(function() use ($importantNotifications) {
            $importantNotifications->markAsRead();
        });

        return $this->responseSuccess([
            'data' => $responseData
        ]);
    }
    
    public function getUnreadCount()
    {
        $unreadCount = me()->getUnreadNotificationsCount();

        return $this->responseSuccess([
            'data' => [
                'formatted' => Num::abbreviate($unreadCount),
                'raw' => $unreadCount
            ]
        ]);
    }

    public function deleteNotification(Request $request)
    {   
        $notificationId = $request->string('notification_id', null);

        if($notificationId->isUuid()) {
            $notificationData = me()->notifications()->where([
                'id' => $notificationId
            ])->first();

            if($notificationData) {
                $notificationData->delete();
            }
        }

        return $this->responseSuccess([
            'data' => null
        ]);
    }
}