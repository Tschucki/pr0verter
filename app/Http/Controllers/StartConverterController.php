<?php

namespace App\Http\Controllers;

use App\Conversion\ConversionSettings;
use App\Enums\ConversionStatus;
use App\Events\FileUploadFailed;
use App\Events\FileUploadSuccessful;
use App\Events\PreviousFilesDeleted;
use App\Http\Requests\StartConverterRequest;
use App\Jobs\ConversionJob;
use App\Models\Conversion;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class StartConverterController extends Controller
{
    private string $sessionId;

    public function __construct()
    {
        $this->sessionId = request()->session()->getId();
    }

    public function __invoke(StartConverterRequest $request)
    {
        $validated = $request->validated();

        $this->deleteOldFiles();

        if ($request->hasFile('file')) {
            $file = $this->handleUploadedFile($request);
        }

        $conversionSettings = ConversionSettings::fromRequest($validated);

        $conversion = Conversion::create([
            ...$conversionSettings->toArray(),
            'status' => ConversionStatus::PENDING,
            'session_id' => $this->sessionId,
            'file_id' => $file->id ?? null,
            'url' => $validated['url'] ?? null,
        ]);

        // ConversionJob::dispatchSync($conversion->id);

        if ($request->hasFile('file')) {
            ConversionJob::dispatch($conversion->id)->onQueue('converter');
        }

        return redirect()->route('conversions.list');
    }

    private function handleUploadedFile(StartConverterRequest $request): File
    {
        $file = $this->storeUploadedFile($request->file('file'));

        if ($file === null) {
            FileUploadFailed::dispatch($this->sessionId);
        }

        FileUploadSuccessful::dispatch($this->sessionId);

        return $file;
    }

    private function storeUploadedFile(UploadedFile $uploadedFile): File
    {
        $fileName = $uploadedFile->hashName();
        $fullPath = $uploadedFile->store(options: 'conversions');

        Log::info('Uploaded File has been stored', [
            'fullPath' => $fullPath,
            'sessionId' => $this->sessionId,
        ]);

        return File::firstOrCreate([
            'session_id' => $this->sessionId,
        ], [
            'disk' => 'conversions',
            'size' => $uploadedFile->getSize(),
            'extension' => $uploadedFile->extension(),
            'filename' => $fileName,
            'session_id' => $this->sessionId,
            'mime_type' => $uploadedFile->getMimeType(),
        ]);
    }

    private function deleteOldFiles(): void
    {
        $countOldFiles = File::where('session_id', $this->sessionId)->count();

        Conversion::where('session_id', $this->sessionId)->delete();
        File::where('session_id', $this->sessionId)->delete();

        if ($countOldFiles > 0) {
            PreviousFilesDeleted::dispatch($this->sessionId);
        }
    }
}
