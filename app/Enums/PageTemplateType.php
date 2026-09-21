<?php

declare(strict_types=1);

namespace App\Enums;

enum PageTemplateType: string
{
    case Homepage = 'homepage';
    case Institutional = 'institutional';
    case Landing = 'landing';
    case Directory = 'directory';
    case Detail = 'detail';
    case Article = 'article';
    case Event = 'event';
    case Career = 'career';
    case Form = 'form';
    case Legal = 'legal';
    case Error = 'error';
}
