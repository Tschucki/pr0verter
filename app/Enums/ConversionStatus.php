<?php

namespace App\Enums;

enum ConversionStatus: string
{
    case DOWNLOADING = 'downloading';
    case PREPARING = 'preparing';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case FINISHED = 'finished';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
}
