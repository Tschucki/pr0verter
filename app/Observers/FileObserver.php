<?php

namespace App\Observers;

use App\Models\File;

class FileObserver
{
    public function deleting(File $file): void
    {
        $file->deleteLocalFile();
    }
}
