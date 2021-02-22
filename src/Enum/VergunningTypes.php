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

namespace App\Enum;

class VergunningTypes
{
    const soll = 'Sollicitant';
    const vpl  = 'Vaste plaats';
    const vkk  = 'Voorkeurskaart';

    public static function all(): array 
    {
        $object = new self();
        $reflection = new \ReflectionClass($object);
        
        return $reflection->getConstants();
    }
}
