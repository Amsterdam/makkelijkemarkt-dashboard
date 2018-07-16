<?php
/*
 *  Copyright (C) 2017 X Gemeente
 *                     X Amsterdam
 *                     X Onderzoek, Informatie en Statistiek
 *
 *  This Source Code Form is subject to the terms of the Mozilla Public
 *  License, v. 2.0. If a copy of the MPL was not distributed with this
 *  file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Enum;

class Roles
{
    const ROLE_USER  = 'Gebruiker';
    const ROLE_SENIOR = 'Senior gebruiker';
    const ROLE_ADMIN = 'Beheerder';

    public static function all() {
        $object = new self();
        $reflection = new \ReflectionClass($object);
        return $reflection->getConstants();
    }
}
