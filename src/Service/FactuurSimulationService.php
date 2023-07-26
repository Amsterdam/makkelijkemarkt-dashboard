<?php

declare(strict_types=1);

namespace App\Service;

class FactuurSimulationService
{
    public static function createPostData(array $data, array $markt)
    {
        $total = self::makeTotal($data['paid'], $data['unpaid']);

        return [
            'dag' => $data['dag']->format('Y-m-d'),
            'marktId' => $markt['id'],
            'products' => [
                'paid' => self::createProductObjects($data['paid'], $markt),
                'total' => self::createProductObjects($total, $markt),
            ],
            'saveFactuur' => false,
            'isSimulation' => true,
        ];
    }

    public static function makeTotal(array $paid, array $unpaid)
    {
        $total = [];
        foreach (array_keys($paid + $unpaid) as $key) {
            $total[$key] = (isset($paid[$key]) ? $paid[$key] : 0) + (isset($unpaid[$key]) ? $unpaid[$key] : 0);
        }

        return $total;
    }

    public static function createProductObjects(array $productFormData, array $markt)
    {
        $products = [];

        foreach ($productFormData as $id => $amount) {
            if ($amount) {
                $product = self::getProductById($id, $markt);
                $products[] = [
                    'id' => $id,
                    'dagvergunningKey' => $product['dagvergunningKey'],
                    'appLabel' => $product['appLabel'],
                    'amount' => $amount,
                ];
            }
        }

        return $products;
    }

    public static function getProductById(int $id, array $markt)
    {
        $key = array_search($id, array_column($markt['products'], 'id'));

        return $markt['products'][$key];
    }
}
