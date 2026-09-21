<?php

declare(strict_types=1);

namespace App\Enums;

enum ButtonVariant: string
{
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Text = 'text';
    case Inverse = 'inverse';
}
