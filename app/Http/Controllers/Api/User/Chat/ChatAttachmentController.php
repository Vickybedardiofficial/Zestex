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

namespace App\Http\Controllers\Api\User\Chat;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Traits\Http\Api\SupportsApiResponses;
use App\Models\MessageAttachment;
use App\Http\Resources\User\Chat\MessageAttachmentResource;
use App\Services\Filesystem\Upload\ImageUploadService;
use App\Services\Filesystem\Upload\VideoUploadService;
use App\Services\Filesystem\Upload\AudioUploadService;
use App\Services\Filesystem\Upload\DocumentUploadService;
use Illuminate\Support\Facades\Storage;

class ChatAttachmentController extends Controller
{
    use SupportsApiResponses;

    public function upload(Request $request)
    {
        $maxSizeMb = (int) config('chat.attachments.max_size_mb', 20);
        $maxSizeKb = $maxSizeMb * 1024;

        $request->validate([
            'file' => ['required', 'file', "max:{$maxSizeKb}"],
        ]);

        $file = $request->file('file');

        if (! $file) {
            return $this->responseValidationError([
                'message' => 'File is required.',
                'errors' => [
                    'file' => ['File is required.']
                ]
            ]);
        }

        $mimeType = $file->getClientMimeType();
        $allowedMimes = $this->getAllowedMimes();

        if (! in_array($mimeType, $allowedMimes, true)) {
            return $this->responseValidationError([
                'message' => 'Unsupported file type.',
                'errors' => [
                    'file' => ['Unsupported file type.']
                ]
            ]);
        }

        $type = $this->detectType($mimeType);
        $disk = static_storage_disk();
        $namespace = 'uploads/messenger/attachments';

        try {
            $uploadData = $this->handleUploadByType($type, $file, $disk, $namespace);

            $attachment = MessageAttachment::create([
                'message_id' => null,
                'user_id' => me()->id,
                'type' => $type,
                'disk' => $disk,
                'path' => $uploadData['path'],
                'mime_type' => $mimeType,
                'size_bytes' => $file->getSize(),
                'original_name' => $file->getClientOriginalName(),
                'duration_seconds' => $uploadData['duration_seconds'] ?? null,
                'width' => $uploadData['width'] ?? null,
                'height' => $uploadData['height'] ?? null,
                'metadata' => $uploadData['metadata'] ?? []
            ]);

            return $this->responseSuccess([
                'data' => MessageAttachmentResource::make($attachment)
            ]);
        } catch (Exception $e) {
            return $this->responseError([
                'message' => $e->getMessage(),
                'errors' => [
                    'file' => [$e->getMessage()]
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function detectType(string $mimeType): string
    {
        $mimes = config('chat.attachments.mimes', []);

        foreach (['image', 'video', 'audio', 'document', 'file'] as $type) {
            if (in_array($mimeType, $mimes[$type] ?? [], true)) {
                return $type;
            }
        }

        return 'file';
    }

    private function getAllowedMimes(): array
    {
        $mimes = config('chat.attachments.mimes', []);
        $allowed = [];

        foreach ($mimes as $list) {
            $allowed = array_merge($allowed, $list);
        }

        return array_values(array_unique($allowed));
    }

    private function handleUploadByType(string $type, $file, string $disk, string $namespace): array
    {
        if ($type === 'image') {
            $imageUploadService = app(ImageUploadService::class);
            $imageData = $imageUploadService
                ->load($file->getRealPath())
                ->compress(80)
                ->setStorageDisk($disk)
                ->setNamespace($namespace)
                ->upload();

            $sizeInfo = @getimagesize($file->getRealPath());

            return [
                'path' => $imageData['image_path'],
                'width' => $sizeInfo[0] ?? null,
                'height' => $sizeInfo[1] ?? null
            ];
        }

        if ($type === 'video') {
            $videoUploadService = app(VideoUploadService::class);
            $temp = $videoUploadService->tempSaveLocally($file);
            $uploadData = $videoUploadService
                ->setStorageDisk($disk)
                ->setNamespace($namespace)
                ->upload($temp['video_path']);

            Storage::disk('local')->delete($temp['video_path']);

            return [
                'path' => $uploadData['video_path'],
                'duration_seconds' => $temp['seconds'] ?? $temp['duration'] ?? null
            ];
        }

        if ($type === 'audio') {
            $audioUploadService = app(AudioUploadService::class);
            $temp = $audioUploadService->tempSaveLocally($file);
            $uploadData = $audioUploadService
                ->setStorageDisk($disk)
                ->setNamespace($namespace)
                ->upload($temp['audio_path']);

            Storage::disk('local')->delete($temp['audio_path']);

            return [
                'path' => $uploadData['audio_path'],
                'duration_seconds' => $temp['duration'] ?? null
            ];
        }

        $documentUploadService = app(DocumentUploadService::class);
        $docData = $documentUploadService
            ->setStorageDisk($disk)
            ->setNamespace($namespace)
            ->upload($file);

        return [
            'path' => $docData['document_path']
        ];
    }
}
