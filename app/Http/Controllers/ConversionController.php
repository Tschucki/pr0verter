<?php

namespace App\Http\Controllers;

use App\Models\Conversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ConversionController extends Controller
{
    public function download(Conversion $conversion, Request $request): BinaryFileResponse
    {
        $conversionWithFile = $conversion->load('file');
        $conversionSessionId = $conversionWithFile->file->session_id;
        $sessionId = $request->session()->getId();

        abort_unless($conversionSessionId === $sessionId, 403);
        abort_unless($conversionWithFile->status === 'finished', 403);
        abort_unless($conversionWithFile->downloadable === true, 403);

        $fileExists = Storage::disk($conversionWithFile->file->disk)->exists($conversionWithFile->file->filename);

        abort_unless($fileExists, 404);

        $fileName = $conversionWithFile->file->filename;
        $disk = $conversionWithFile->file->disk;

        return response()->download(Storage::disk($disk)->path($fileName));
    }
}
