<?php

declare(strict_types=1);

namespace App\Enums;

enum SubmissionType: string
{
    case Consultation = 'consultation';
    case Rfp = 'rfp';
    case Partnership = 'partnership';
    case Media = 'media';
    case Contact = 'contact';
}
