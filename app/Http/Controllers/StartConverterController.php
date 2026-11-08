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
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class StartConverterController extends Controller
{
    public function __invoke(StartConverterRequest $request)
    {
        $validated = $request->validated();

        $this->ensureSessionIsStored($request);

        $this->deleteOldFiles();

        if ($request->hasFile('file')) {
            $file = $this->handleUploadedFile($request);
        }

        $conversionSettings = ConversionSettings::fromRequest($validated);

        $conversion = Conversion::create([
            ...$conversionSettings->toArray(),
            'status' => ConversionStatus::PENDING,
            'session_id' => Session::getId(),
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
            FileUploadFailed::dispatch(Session::getId());
        }

        FileUploadSuccessful::dispatch(Session::getId());

        return $file;
    }

    private function storeUploadedFile(UploadedFile $uploadedFile): File
    {
        $fileName = $uploadedFile->hashName();
        $fullPath = $uploadedFile->store(options: 'conversions');

        Log::info('Uploaded File has been stored', [
            'fullPath' => $fullPath,
            'sessionId' => Session::getId(),
        ]);

        return File::firstOrCreate([
            'session_id' => Session::getId(),
        ], [
            'disk' => 'conversions',
            'size' => $uploadedFile->getSize(),
            'extension' => $uploadedFile->extension(),
            'filename' => $fileName,
            'session_id' => Session::getId(),
            'mime_type' => $uploadedFile->getMimeType(),
        ]);
    }

    /**
     * Files and conversions reference the session row by foreign key, but the
     * database session handler only writes that row after the response. A
     * session that is not stored yet (first request, or its row was garbage
     * collected while the page stayed open) is written now instead.
     */
    private function ensureSessionIsStored(Request $request): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $session = $request->session();

        $stored = DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('id', $session->getId())
            ->exists();

        if ($stored) {
            return;
        }

        // The handler may still believe the row exists and would only run an UPDATE.
        $session->setExists(false);
        $session->save();
        $session->start();
    }

    private function deleteOldFiles(): void
    {
        $countOldFiles = File::where('session_id', Session::getId())->count();

        Conversion::where('session_id', Session::getId())->delete();
        File::where('session_id', Session::getId())->delete();

        if ($countOldFiles > 0) {
            PreviousFilesDeleted::dispatch(Session::getId());
        }
    }
}
