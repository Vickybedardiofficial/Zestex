<?php

namespace App\Http\Resources\User\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $url = storage_url($this->path, $this->disk);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'url' => $url,
            'preview_url' => $url,
            'download_url' => $url,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'original_name' => $this->original_name,
            'duration_seconds' => $this->duration_seconds,
            'width' => $this->width,
            'height' => $this->height,
            'meta' => $this->metadata ?? [],
        ];
    }
}
