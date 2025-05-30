<?php

namespace App\Http\Controllers;

use App\Enums\ConversionStatus;
use App\Events\ConversionUpdated;
use App\Models\Conversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConversionController extends Controller
{
    public function download(Conversion $conversion, Request $request): BinaryFileResponse
    {
        $conversionWithFile = $conversion->load('file');

        $file = $conversionWithFile->file;

        if ($file === null) {
            abort(404);
        }

        $conversionSessionId = $file->session_id;
        $sessionId = $request->session()->getId();

        if ($file->isPublic() === false) {
            abort_unless($conversionSessionId === $sessionId, 403);
        }

        abort_unless($conversionWithFile->status === ConversionStatus::FINISHED, 403);
        abort_unless($conversionWithFile->downloadable === true, 403);

        $fileExists = Storage::disk($conversionWithFile->file->disk)->exists($conversionWithFile->file->filename);

        abort_unless($fileExists, 404);

        $fileName = $conversionWithFile->file->filename;
        $disk = $conversionWithFile->file->disk;

        return response()->download(Storage::disk($disk)->path($fileName));
    }

    public function togglePublicFlag(Conversion $conversion, Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $conversionSessionId = $conversion->file->session_id;

        abort_unless($conversionSessionId === $sessionId, 403);

        $conversion->file->update([
            'public' => ! $conversion->file->public,
        ]);

        ConversionUpdated::dispatch($conversion->id);

        return response()->json([
            'public' => $conversion->file->public,
        ]);
    }

    public function cancel(Conversion $conversion, Request $request): JsonResponse
    {
        $sessionId = $request->session()->getId();
        $conversionSessionId = $conversion->file->session_id;

        abort_unless($conversionSessionId === $sessionId, 403);

        if ($conversion->status === ConversionStatus::FINISHED) {
            return response()->json([
                'message' => 'Conversion already finished.',
                'status' => ConversionStatus::FINISHED,
            ]);
        }

        $conversion->update([
            'status' => ConversionStatus::CANCELED,
        ]);

        return response()->json([
            'message' => 'Conversion canceled successfully.',
            'status' => ConversionStatus::CANCELED,
        ]);
    }
}
