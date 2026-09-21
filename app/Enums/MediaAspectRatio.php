<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaAspectRatio: string
{
    case Natural = 'natural';
    case Landscape = 'landscape';
    case Portrait = 'portrait';
    case Square = 'square';
    case Cinematic = 'cinematic';
}
