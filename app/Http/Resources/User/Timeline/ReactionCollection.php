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

namespace App\Http\Resources\User\Timeline;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReactionCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(function($item) {
            $hasReacted = $this->checkIfReactionMade($item->users);
            $unifiedId = strtolower((string) $item->unified_id);

            $reactionData = [
                'unified_id' => $unifiedId,
                'image_url' => reaction_image_url($unifiedId),
                'native_symbol' => null,
                'total' => $item->reactions_count,
                'has_reacted' => $hasReacted
            ];

            return $reactionData;
        })->toArray();
    }

    private function checkIfReactionMade(array $usersList): bool
    {
        if(auth_check()) {
            return in_array(me()->id, $usersList);
        }

        return false;
    }
}
