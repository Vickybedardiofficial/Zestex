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

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'has_voted' => $this->checkPollIsVoted(),
            'is_expired' => (! empty($this->expires_at)),
            'choices' => $this->getPollChoices(),
            'votes' => $this->votes,
            'voter_users' => $this->getPollVoters(),
            'is_anonymous' => $this->is_anonymous,
            'metadata' => $this->metadata
        ];
    }

    private function getPollVoters(): array
    {
        $voterIds = collect($this->normalizeVotes())->take(7)->pluck('user_id')->toArray();

        if(is_array($voterIds)) {
            $voters = User::whereIn('id', $voterIds)->get(['id', 'avatar', 'first_name', 'last_name']);

            return $voters->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'avatar_url' => $item->avatar_url
                ];
            })->toArray();
        }

        return [];
    }

    private function getPollChoices(): array
    {
        $choices = collect($this->choices)->map(function($item, $choiceIndex) {
            return $this->normalizeChoiceItem($item, $choiceIndex);
        });

        if(auth_check()) {
            return $choices->map(function($item, $choiceIndex) {

                $item['has_voted_choice'] = ! empty(Arr::first($this->normalizeVotes(), function ($value) use ($choiceIndex) {
                    return ($value['user_id'] === me('id') && $value['choice_id'] === $choiceIndex);
                }));

                return $item;

            })->toArray();
        }

        return $choices->toArray();
    }

    private function checkPollIsVoted(): bool
    {
        if(auth_check()) {
            return ! empty(Arr::first($this->normalizeVotes(), function ($value) {
                return $value['user_id'] === me('id');
            }));
        }

        return false;
    }

    private function normalizeVotes(): array
    {
        $votes = is_array($this->votes) ? $this->votes : [];

        return array_values(array_filter($votes, function($vote) {
            return is_array($vote) && isset($vote['user_id']) && isset($vote['choice_id']);
        }));
    }

    private function normalizeChoiceItem(mixed $item, int $choiceIndex): array
    {
        if (is_string($item)) {
            return [
                'choice_text' => $item,
                'percentage' => 0,
                'choice_id' => $choiceIndex,
                'has_voted_choice' => false
            ];
        }

        if (! is_array($item)) {
            return [
                'choice_text' => '',
                'percentage' => 0,
                'choice_id' => $choiceIndex,
                'has_voted_choice' => false
            ];
        }

        return [
            'choice_text' => data_get($item, 'choice_text', data_get($item, 'text', '')),
            'percentage' => (int) data_get($item, 'percentage', 0),
            'choice_id' => (int) data_get($item, 'choice_id', $choiceIndex),
            'has_voted_choice' => (bool) data_get($item, 'has_voted_choice', false)
        ];
    }
}
