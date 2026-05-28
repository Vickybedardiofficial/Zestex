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

namespace App\Http\Controllers\Api\User\Timeline;

use Exception;
use App\Enums\Post\PostType;
use Illuminate\Http\Request;
use App\Enums\Media\MediaType;
use App\Enums\Media\MediaStatus;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Services\Filesystem\Upload\AudioUploadService;
use App\Services\Filesystem\RoundRobin\RoundRobinService;
use App\Traits\Http\Controllers\Api\User\Timeline\ValidatesPostAudio;
use App\Traits\Http\Controllers\Api\User\Timeline\InteractsWithDraftPost;

class PostAudioController extends Controller
{
    use InteractsWithDraftPost,
        SupportsApiResponses, 
        ValidatesPostAudio;
    
    private $audioUploadService;
    private $roundRobinService;
    
    public function __construct(AudioUploadService $audioUploadService, RoundRobinService $roundRobinService)
    {
        $this->audioUploadService = $audioUploadService;
        $this->roundRobinService = $roundRobinService;
    }

    public function uploadAudio(Request $request)
    {
        $postAudioFile = $request->file('audio');

        $this->validatePostAudio([
            'audio' => $postAudioFile
        ]);

        $this->fetchOrInitializeDraftPost();

        if($this->draftPost->type->isTextified()) {

            if(! $this->draftPost->exists) {
                $this->draftPost->type = PostType::AUDIO;
                $this->draftPost->save();
            }

            try {
                $audioData = $this->audioUploadService
                    ->setStorageDisk($this->roundRobinService->getNextDisk())
                    ->tempSaveLocally($postAudioFile);

                $this->draftPost->media()->create([
                    'source_path' => $audioData['audio_path'],
                    'type' => MediaType::AUDIO,
                    'status' => MediaStatus::PROCESSING,
                    'disk' => $audioData['disk'],
                    'extension' => $postAudioFile->getClientOriginalExtension(),
                    'mime' => $postAudioFile->getClientMimeType(),
                    'size' => $postAudioFile->getSize(),
                    'metadata' => [
                        'duration' => $audioData['duration'],
                        'file_name' => $postAudioFile->getClientOriginalName()
                    ]
                ]);

                return $this->responseSuccess([
                    'data' => [
                        'file_name' => $postAudioFile->getClientOriginalName(),
                        'size' => $postAudioFile->getSize(),
                        'extension' => $postAudioFile->getClientOriginalExtension()
                    ]
                ]);

            } catch (Exception $e) {
                return $this->responseValidationError([
                    'message' => $e->getMessage(),
                    'errors' => [
                        'audio' => [
                            $e->getMessage()
                        ]
                    ]
                ]);
            }
        }

        else{
            $errorMessage = __('post.validation.wrong_type_attachment', ['file_type' => __('labels.audio')]);
            
            return $this->responseValidationError([
                'message' => $errorMessage,
                'errors' => [
                    'audio' => [
                        $errorMessage
                    ]
                ]
            ]);
        }
    }
}
