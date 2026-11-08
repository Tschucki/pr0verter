<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown from the yt-dlp progress callback to stop a download whose
 * conversion was canceled or deleted while it was running.
 */
class DownloadAbortedException extends RuntimeException {}
