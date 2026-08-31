<?php

namespace App\Enums;

enum SurveyResponseStatus: string
{
    case DRAFT = 'draft';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}