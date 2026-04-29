<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversion;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ThumbnailController extends Controller
{
    public function __invoke(Conversion $conversion): BinaryFileResponse|Response
    {
        $conversion->load('file');

        abort_if($conversion->thumbnail_path === null, 404);

        $file = $conversion->file;
        if ($file === null || $file->isPublic() === false) {
            abort_unless($file?->session_id === Session::getId(), 403);
        }

        $disk = Storage::disk('conversions');
        abort_unless($disk->exists($conversion->thumbnail_path), 404);

        $cacheControl = ($file?->isPublic() === true)
            ? 'public, max-age=86400'
            : 'private, max-age=300';

        $response = response()->file($disk->path($conversion->thumbnail_path), [
            'Content-Type' => 'image/jpeg',
        ]);

        $response->headers->set('Cache-Control', $cacheControl);

        return $response;
    }
}
