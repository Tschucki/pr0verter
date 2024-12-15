<?php

namespace App\Contracts;

interface Converter
{
    public function getFilePath(): ?string;

    public function convert(): bool;

    public function isResponsible(): bool;
}
