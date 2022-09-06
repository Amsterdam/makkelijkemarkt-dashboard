<?php
/*
 *  Copyright (C) 2021 X Gemeente
 *                     X Amsterdam
 *                     X Onderzoek, Informatie en Statistiek
 *
 *  This Source Code Form is subject to the terms of the Mozilla Public
 *  License, v. 2.0. If a copy of the MPL was not distributed with this
 *  file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace App\Enum;

class ActiveStates
{
    const ROLE_ACTIVE = '1';
    const ROLE_INACTIVE = '0';

    public static function all(): array
    {
        $object = new self();
        $reflection = new \ReflectionClass($object);

        return $reflection->getConstants();
    }
}
