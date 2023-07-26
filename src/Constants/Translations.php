<?php

declare(strict_types=1);

namespace App\Constants;

// All translations should be from English to Dutch!!!
class Translations
{
    public const WEEKDAYS = [
        'monday' => 'maandag',
        'tuesday' => 'dinsdag',
        'wednesday' => 'woensdag',
        'thursday' => 'donderdag',
        'friday' => 'vrijdag',
        'saturday' => 'zaterdag',
        'sunday' => 'zondag',
    ];

    public const VARIANTS = [
        'standard' => 'standaard',
        'daysOfWeek' => 'weekdagen',
        'specific' => 'specifieke data',
    ];

    public const UNITS = [
        'unit' => 'per stuk',
        'one-off' => 'eenmalig',
        'meters' => 'meters',
        'meters-groot' => 'meters-groot',
        'meters-klein' => 'meters-klein',
        'meters-totaal' => 'meters-totaal',
    ];
}
