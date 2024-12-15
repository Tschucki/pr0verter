<?php

namespace App\Contracts;

use App\Transporters\MediaOperationTransporter;
use Closure;

interface MediaOperation
{
    public function filePath(): string;

    public function handle(MediaOperationTransporter $transporter, Closure $next): void;

    public function log(): void;
}
