<?php

declare(strict_types=1);

namespace App\Service;

class BtwPlanEditingService
{
    // Takes the form data and the current BTW Plan and determine if we need to send a PUT request to the API.
    public function handleUpdateBtwPlanForm(array $data): array
    {
        $btwPlan = $data['btwPlan'];
        $dateFrom = $data['dateFrom']->format('Y-m-d');

        $changes = array_diff(
            [
                $btwPlan['btwType'],
                $btwPlan['dateFrom'],
                $btwPlan['marktId'],
            ],
            [
                $data['btwType'],
                $dateFrom,
                $data['markt'],
            ]
        );

        if (!empty($changes)) {
            // The form only returns a label, so we need to look back in the form options to
            // match the chosen label with an id for our POST request.
            $btwTypeKey = array_search($data['btwType'], array_column($data['btwTypes'], 'label'));

            return [
                'btwPlanId' => $btwPlan['id'],
                'tariefSoortId' => $btwPlan['tariefSoortId'],
                'btwTypeId' => $data['btwTypes'][$btwTypeKey]['id'],
                'dateFrom' => ['date' => $dateFrom],
                'marktId' => $data['markt'],
            ];
        }

        return [];
    }

    // Transform the data from the form to prepare a new BTW plan POST to the API.
    public function handleCreateBtwPlanForm($data): array
    {
        $dateFrom = $data['dateFrom']->format('Y-m-d');

        // The form only returns a label, so we need to look back in the form options to
        // match the chosen label with an id for our POST request.
        $btwTypeKey = array_search($data['btwType'], array_column($data['btwTypes'], 'label'));
        $tariefSoortKey = array_search($data['tariefSoort'], array_column($data['tariefSoorten'], 'label'));

        return [
            'tariefSoortId' => $data['tariefSoorten'][$tariefSoortKey]['id'],
            'btwTypeId' => $data['btwTypes'][$btwTypeKey]['id'],
            'dateFrom' => ['date' => $dateFrom],
            'marktId' => $data['markt'],
        ];
    }

    // Takes all plans sent from the API and determine if they are active.
    // We need to determine for generic tarieven if they are active, but we also
    // need to determine this for specific markets.
    public function mapActivePlans($plans)
    {
        // Hold all the tarief id's and the id of the active BTW plans as value.
        // These are all BTW plans that are not related to a specific market.
        $genericPlans = [];

        // Hold market specfic tarief id's and btw plan id's
        $marketPlans = [];

        foreach ($plans as $key => $plan) {
            $plans[$key]['isActive'] = false;

            if ($plan['marktId']) {
                $marketPlans = $this->mutateMarketPlansIfActive($marketPlans, $plans, $plan);
                continue;
            }

            $genericPlans = $this->mutateGenericPlansIfActive($genericPlans, $plans, $plan);
        }

        foreach ($marketPlans as $market) {
            foreach ($market as $tariefSoort => $btwPlanId) {
                $plans = $this->setPlanActive($plans, $btwPlanId);
            }
        }

        foreach ($genericPlans as $tariefSoortId => $btwPlanId) {
            $plans = $this->setPlanActive($plans, $btwPlanId);
        }

        return $plans;
    }

    private function getPlanObjectById(array $plans, int $id): array
    {
        return $plans[array_search($id, array_column($plans, 'id'))];
    }

    // Update the market plans array with active BTW plan id's if one of these checks pass
    private function mutateMarketPlansIfActive(array $marketPlans, array $plans, array $plan): array
    {
        $today = (new \DateTime())->format('Y-m-d');
        $tariefSoortId = $plan['tariefSoortId'];

        if ($plan['dateFrom'] > $today) {
            return $marketPlans;
        }

        if (!isset($marketPlans[$plan['marktId']])) {
            $marketPlans[$plan['marktId']] = [];
        }

        if (!isset($marketPlans[$plan['marktId']][$tariefSoortId])) {
            $marketPlans[$plan['marktId']][$tariefSoortId] = $plan['id'];

            return $marketPlans;
        }

        $currentActive = $this->getPlanObjectById($plans, $marketPlans[$plan['marktId']][$tariefSoortId]);

        // Overwrite current active btw plan if it's closer to today and not in the future.
        if ($plan['dateFrom'] > $currentActive['dateFrom']) {
            $marketPlans[$plan['marktId']][$tariefSoortId] = $plan['id'];
        }

        return $marketPlans;
    }

    // Update the market plans array with active BTW plan id's if one of these checks pass
    private function mutateGenericPlansIfActive(array $genericPlans, array $plans, array $plan): array
    {
        $today = (new \DateTime())->format('Y-m-d');
        $tariefSoortId = $plan['tariefSoortId'];

        if ($plan['dateFrom'] > $today) {
            return $genericPlans;
        }

        if (!isset($genericPlans[$tariefSoortId])) {
            $genericPlans[$tariefSoortId] = $plan['id'];

            return $genericPlans;
        }

        $currentActive = $this->getPlanObjectById($plans, $genericPlans[$tariefSoortId]);

        if ($plan['dateFrom'] <= $today
            && $plan['dateFrom'] >= $currentActive['dateFrom']
        ) {
            $genericPlans[$tariefSoortId] = $plan['id'];
        }

        return $genericPlans;
    }

    // Set an item in the $plans array to active.
    private function setPlanActive(array $plans, int $id): array
    {
        $key = array_search($id, array_column($plans, 'id'));
        $plans[$key]['isActive'] = true;

        return $plans;
    }
}
