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

namespace App\Http\Controllers\Downloads\Documents;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Media;

class DocumentDownloadController extends Controller
{
    public function downloadDocument(Request $request) {
        $postMedia = Media::with('post')->findOrFail($request->route('media_id'));
        $filePath = $postMedia->source_path;
        $disk = $postMedia->disk;

        if (! Storage::disk($disk)->exists($filePath)) {
            abort(404);
        }

        $metadata = $postMedia->metadata;
        $metadata['downloads'] = ($metadata['downloads'] ?? 0) + 1;

        $fileName = $metadata['file_name'] ?? basename($filePath);

        $postMedia->update([
            'metadata' => $metadata
        ]);

        return Storage::disk($disk)->download($filePath, $fileName);
    }
}
