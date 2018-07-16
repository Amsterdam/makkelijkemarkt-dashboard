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

class ActiveStates
{
    const ROLE_ACTIVE   = '1';
    const ROLE_INACTIVE = '0';

    public static function all() {
        $object = new self();
        $reflection = new \ReflectionClass($object);
        return $reflection->getConstants();
    }
}
