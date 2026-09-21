<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentSelectionMode: string
{
    case Manual = 'manual';
    case Featured = 'featured';
    case Latest = 'latest';
    case Related = 'related';
    case Category = 'category';
}
