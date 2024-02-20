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

class Roles
{
    public const ROLE_USER = 'Gebruiker';
    public const ROLE_SENIOR = 'Senior gebruiker';
    public const ROLE_ADMIN = 'Beheerder';
    public const ROLE_ACCOUNTANT = 'Accountant';

    public static function all(): array
    {
        $object = new self();
        $reflection = new \ReflectionClass($object);

        return $reflection->getConstants();
    }
}
