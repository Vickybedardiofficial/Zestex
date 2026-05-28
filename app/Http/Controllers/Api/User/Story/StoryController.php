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

namespace App\Http\Controllers\Api\User\Story;

use Throwable;
use App\Models\Story;
use App\Rules\X\XRule;
use App\Models\StoryFrame;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Enums\Story\StoryStatus;
use App\Http\Controllers\Controller;
use App\Events\User\Story\StoryCreatedEvent;
use App\Actions\Story\DeleteStoryFrameAction;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Http\Resources\User\Story\StoryResource;
use App\Http\Resources\User\Story\FeedCollection;
use App\Http\Resources\User\Story\ViewCollection;
use App\Http\Resources\User\Story\StoryCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Traits\Http\Controllers\Api\User\Story\InteractsWithDraftStoryFrame;

class StoryController extends Controller
{
    use InteractsWithDraftStoryFrame,
        AuthorizesRequests,
        SupportsApiResponses;

    public function __construct()
    {
        $this->fetchOrInitializeDraftStoryFrame();
    }

    public function getStories(Request $request, string $storyId)
    {
        if(Str::isUuid($storyId)) {
            $storyData = Story::active()->where('story_uuid', $storyId)->withRelations()->first();
            
            if($storyData) {
                $otherRelevantStories = Story::active()->whereNot('story_uuid', $storyId)->withRelations()->get();
                $stories = $otherRelevantStories->prepend($storyData);

                return $this->responseSuccess([
                    'data' => StoryCollection::make($stories)
                ]);
            }
        }

        return $this->responseResourceNotFoundError('Story', $storyId);
    }

    public function getFeed(Request $request)
    {
        $storiesFeed = Story::active()->with([
            'user:id,avatar,first_name,last_name',
            'frames.views'
        ])->latest('updated_at')->get();

        if($storiesFeed->isNotEmpty()) {
            return $this->responseSuccess([
                'data' => FeedCollection::make($storiesFeed)
            ]);
        }

        return $this->responseNotFoundError();
    }

    public function create(Request $request)
    {
        $storyMedia = $this->draftStoryFrame->media->first();

        if(empty($storyMedia)) {
            return $this->responseError([
                'message' => 'Please upload a media file before publishing this story.',
                'errors' => [
                    'media_file' => [
                        'Please upload a media file before publishing this story.'
                    ]
                ]
            ]);
        }

        else{
            $request->validate([
                'content' => ['nullable', 'string', XRule::join('max', config('story.validation.content.max'))]
            ]);

            $isVideo = $this->draftStoryFrame->type ? $this->draftStoryFrame->type->isVideo() : false;

            $this->draftStoryFrame->update([
                'content' => e($request->string('content')),
                'status' => $isVideo ? StoryStatus::PROCESSING : StoryStatus::ACTIVE,
                'expires_at' => now()->addHours(24),
                'duration_seconds' => $isVideo ? 1 : config('story.image_clip_size')
            ]);

            event(new StoryCreatedEvent($this->draftStoryFrame));

            if(empty($isVideo)) {
                $myStory = me()->story()->withRelations()->first();

                return $this->responseSuccess([
                    'data' => StoryResource::make($myStory)
                ]);
            }

            return $this->responseSuccess([
                'data' => null
            ]);
        }
    }

    public function recordView(Request $request)
    {
        $storyFrameId = $request->integer('frame_id');

        if(is_positive($storyFrameId)) {
            $frameData = StoryFrame::active()->where('id', $storyFrameId)->first();

            if($frameData) {
                $isSeen = $frameData->views()->where('user_id', me()->id)->exists();

                if(empty($isSeen)) {
                    $frameData->views()->create([
                        'user_id' => me()->id,
                        'viewed_at' => now()
                    ]);

                    $frameData->increment('views_count');
                }

                return $this->responseSuccess([
                   'data' => null 
                ]);
            }
        }

        return $this->responseResourceNotFoundError('StoryFrame', $storyFrameId);
    }

    public function deleteStory(Request $request)
    {
        $storyFrameId = $request->integer('frame_id');

        if(is_positive($storyFrameId)) {
            $frameData = StoryFrame::where('id', $storyFrameId)->with('story')->first();

            try {
                $this->authorize('delete', $frameData);

                if($frameData) {
                    (new DeleteStoryFrameAction($frameData))->execute();
                }
    
                return $this->responseSuccess([
                    'data' => null 
                ]);
            } catch (Throwable $th) {
                return $this->responseResourceNotFoundError('StoryFrame', $storyFrameId);
            }
        }

        return $this->responseResourceNotFoundError('StoryFrame', $storyFrameId);
    }
    
    public function getStoryViews(Request $request, $frameId)
    {
        if(is_positive($frameId)) {
            $frameData = StoryFrame::active()->where('id', $frameId)->first();

            if($frameData) {
                $frameViews = $frameData->views()->withUser()->get();

                return $this->responseSuccess([
                   'data' => ViewCollection::make($frameViews)
                ]);
            }
        }

        return $this->responseResourceNotFoundError('StoryFrame', $frameId);
    }
}
