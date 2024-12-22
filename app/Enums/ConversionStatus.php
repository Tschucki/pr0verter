<?php

namespace App\Enums;

enum ConversionStatus
{
    public const PREPARING = 'preparing';

    public const PENDING = 'pending';

    public const PROCESSING = 'processing';

    public const FINISHED = 'finished';

    public const FAILED = 'failed';

    public const CANCELED = 'canceled';
}
