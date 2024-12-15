<?php

namespace App\Converters;

use App\Contracts\Converter;

class VideoConverter implements Converter
{
    public function __construct(){}

    public function getFilePath(): ?string
    {
        return '';
    }

    public function getSourceFile(): bool
    {
       return false;
    }

    public function convert(): bool
    {
        return false;
    }
}
