<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\Translations;

class TarievenplanService
{
    public const VARIANTS = [
        'STANDARD' => 'standard',
        'DAYS_OF_WEEK' => 'daysOfWeek',
        'SPECIFIC' => 'specific',
    ];

    public static function preparePostData(array $data)
    {
        if (isset($data['weekdays'])) {
            $data['weekdays'] = TranslationService::translateArray($data['weekdays'], Translations::WEEKDAYS, true);
        }

        return $data;
    }
}
