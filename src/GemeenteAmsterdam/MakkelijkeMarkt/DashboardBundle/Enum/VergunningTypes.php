<?php

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Enum;

class VergunningTypes
{
    const soll = 'Sollicitant';
    const vpl  = 'Vaste plaats';
    const vkk  = 'Voorkeurskaart';

    public static function all() {
        $object = new self();
        $reflection = new \ReflectionClass($object);
        return $reflection->getConstants();
    }
}
