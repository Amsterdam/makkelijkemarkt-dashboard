<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MarktApi
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var Security
     */
    protected $security;

    /**
     * @var string Base uri of makkelijke markt api
     */
    protected $marktApi;

    /**
     * @var string
     */
    protected $mmAppKey;

    public function __construct(HttpClientInterface $client, Security $security, string $marktApi, string $mmAppKey)
    {
        $this->client = $client;
        $this->security = $security;
        $this->marktApi = $marktApi;
        $this->mmAppKey = $mmAppKey;
    }

    protected function makeRequest(string $method, string $url, array $options = [], bool $addAuthorization = true): ResponseInterface
    {
        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }

        if ($this->security->getUser() && $addAuthorization) {
            /** @var User $user */
            $user = $this->security->getUser();
            $options['headers']['Authorization'] = 'Bearer '.$user->getToken();
        }

        $options['headers']['MmAppKey'] = $this->mmAppKey;

        return $this->client->request($method, rtrim($this->marktApi, '/').$url, $options);
    }

    public function login(string $username, string $password)
    {
        return $this->makeRequest(
            'POST',
            '/login/basicUsername/',
            ['json' => ['username' => $username, 'password' => $password, 'clientApp' => 'dashboard']],
            false
        )->toArray();
    }

    public function getMarkt(int $id): array
    {
        return $this->makeRequest('GET', '/markt/'.$id)->toArray();
    }

    public function getMarkten(): array
    {
        return $this->makeRequest('GET', '/markt/')->toArray();
    }

    public function getNonExpiredMarkten(): array
    {
        $markten = $this->getMarkten();
        $filteredMarkten = array_filter($markten, function ($markt) {
            if (true === $markt['marktBeeindigd']) {
                return false;
            }

            return true;
        });

        return $filteredMarkten;
    }

    public function getExpiredMarkten(): array
    {
        $markten = $this->getMarkten();
        $filteredMarkten = array_filter($markten, function ($markt) {
            if (true === $markt['marktBeeindigd']) {
                return true;
            }

            return false;
        });

        return $filteredMarkten;
    }

    public function getMarktenByName(): array
    {
        $markten = $this->makeRequest('GET', '/markt/')->toArray();

        $marktNamen = [];
        foreach ($markten as $markt) {
            $marktNamen[] = $markt['naam'];
        }

        return $marktNamen;
    }

    public function getDagvergunningen(array $filter, int $listOffset, int $listLength): array
    {
        return $this->makeRequest(
            'GET',
            '/dagvergunning/',
            ['query' => array_merge($filter, ['listOffset' => $listOffset, 'listLength' => $listLength])]
        )->toArray();
    }

    public function getDagvergunningenByDate(int $koopmanId, \DateTime $startDate, \DateTime $endDate): array
    {
        return $this->makeRequest(
            'GET',
            '/dagvergunning_by_date/'.$koopmanId.'/'.$startDate->format('Y-m-d').'/'.$endDate->format('Y-m-d')
        )->toArray();
    }

    public function getKoopman(int $id): array
    {
        return $this->makeRequest(
            'GET',
            '/koopman/id/'.$id
        )->toArray();
    }

    public function getKoopmannen(array $filter, int $listOffset, int $listLength): array
    {
        $response = $this->makeRequest(
            'GET',
            '/koopman/',
            ['query' => array_merge($filter, ['listOffset' => $listOffset, 'listLength' => $listLength])]
        );

        $content = $response->toArray();
        $fullListLengthHeaders = $response->getHeaders()['x-api-listsize'];
        $content = [
            'fullListLength' => (int) reset($fullListLengthHeaders),
            'responseListLength' => count($content),
            'results' => $content,
        ];

        return $content;
    }

    public function toggleHandhavingsverzoek(int $id, \DateTime $date)
    {
        return $this->makeRequest(
            'POST',
            '/koopman/toggle_handhavingsverzoek/'.$id.'/'.$date->format('Y-m-d')
        );
    }

    public function postMarkt(int $id, array $data)
    {
        return $this->makeRequest(
            'POST',
            '/markt/'.$id,
            ['json' => $data]
        );
    }

    public function resetAudit(int $marktId, \DateTime $datum)
    {
        return $this->makeRequest(
            'POST',
            '/audit_reset/'.$marktId.'/'.$datum->format('Y-m-d')
        );
    }

    public function getLijstenMetDatum(int $marktId, \DateTime $startDate, \DateTime $endDate, array $types = []): array
    {
        return $this->makeRequest(
            'GET',
            '/lijst/week/'.$marktId.'/'.implode('|', $types).'/'.$startDate->format('Y-m-d').'/'.$endDate->format('Y-m-d')
        )->toArray();
    }

    public function getAccount(int $id): array
    {
        return $this->makeRequest('GET', '/account/'.$id)->toArray();
    }

    public function getAccounts(int $active = -1, int $locked = -1)
    {
        return $this->makeRequest(
            'GET',
            '/account/',
            ['query' => ['active' => $active, 'locked' => $locked]]
        )->toArray();
    }

    public function putAccount(int $id, array $data): array
    {
        return $this->makeRequest(
            'PUT',
            '/account/'.$id,
            ['json' => $data]
        )->toArray();
    }

    public function putPassword(int $id, array $data): array
    {
        return $this->makeRequest(
            'PUT',
            '/account_password/'.$id,
            ['json' => $data]
        )->toArray();
    }

    public function postAccount(array $data)
    {
        return $this->makeRequest(
            'POST',
            '/account/',
            ['json' => $data]
        );
    }

    public function unlockAccount(int $accountId)
    {
        return $this->makeRequest('POST', '/account/unlock/'.$accountId);
    }

    public function getTokensByAccount(int $accountId, int $listOffset, int $listLength)
    {
        $response = $this->makeRequest(
            'GET',
            '/account/'.$accountId.'/tokens',
            ['query' => ['listOffset' => $listOffset, 'listLength' => $listLength]]
        );

        $content = $response->toArray();
        $fullListLengthHeaders = $response->getHeaders()['x-api-listsize'];
        $content = [
            'fullListLength' => (int) reset($fullListLengthHeaders),
            'responseListLength' => count($content),
            'results' => $content,
        ];

        return $content;
    }

    // The following functions are V1 endpoints (before flexibele tarieven)
    // TODO delete when V2 runs smoothly on PRD
    public function getTariefPlan(int $id): array
    {
        return $this->makeRequest('GET', '/tariefplannen/get/'.$id)->toArray();
    }

    public function deleteTariefPlan(int $id): void
    {
        $this->makeRequest('DELETE', '/tariefplannen/delete/'.$id);
    }

    public function getTariefplannenByMarktId(int $marktId): array
    {
        return $this->makeRequest('GET', '/tariefplannen/list/'.$marktId)->toArray();
    }

    public function postLineairTariefplan(int $marktId, array $data)
    {
        return $this->makeRequest('POST', '/tariefplannen/'.$marktId.'/create/lineair', ['json' => $data]);
    }

    public function postConcreetTariefplan(int $marktId, array $data)
    {
        return $this->makeRequest('POST', '/tariefplannen/'.$marktId.'/create/concreet', ['json' => $data]);
    }

    public function updateLineairTariefplan(int $tariefPlanId, array $data)
    {
        return $this->makeRequest('POST', '/tariefplannen/'.$tariefPlanId.'/update/lineair', ['json' => $data]);
    }

    public function updateConcreetTariefplan(int $tariefPlanId, array $data)
    {
        return $this->makeRequest('POST', '/tariefplannen/'.$tariefPlanId.'/update/concreet', ['json' => $data]);
    }

    // Below here are all the V2 endpoints for Flexibele Tarieven
    public function getTarievenplan(int $id): array
    {
        return $this->makeRequest('GET', "/tarievenplan/$id")->toArray();
    }

    public function getTarievenplannenByMarktId(int $marktId): array
    {
        return $this->makeRequest('GET', "/tarievenplannen/markt/$marktId")->toArray();
    }

    public function updateTarievenplan(int $id, array $data)
    {
        return $this->makeRequest('PUT', "/tarievenplan/update/$id", ['json' => $data]);
    }

    public function deleteTarievenplan(int $id)
    {
        $this->makeRequest('DELETE', "/tarievenplan/$id")->toArray();
    }

    public function createTarievenplan(int $marktId, string $type, array $data): void
    {
        $this->makeRequest('POST', "/tarievenplan/create/$type/$marktId", ['json' => $data]);
    }

    public function getActiveTariefSoorten(string $type = ''): array
    {
        return $this->makeRequest('GET', "/tariefsoorten_active/$type")->toArray();
    }

    public function createTariefSoort(array $data = []): void
    {
        $this->makeRequest('POST', '/tariefsoort', ['json' => $data]);
    }

    public function getTariefSoortById(int $id): array
    {
        return $this->makeRequest('GET', "/tariefsoort/$id")->toArray();
    }

    public function updateTariefSoort(int $id, array $data): void
    {
        $this->makeRequest('PUT', "/tariefsoort/$id", ['json' => $data]);
    }

    public function simulateFactuur(array $data)
    {
        return $this->makeRequest('POST', '/flex/dagvergunning/', ['json' => $data])->toArray();
    }

    // TODO remove this temporary endpoint when we merged it with /markt/{id}
    public function getMarktFlex(int $id): array
    {
        return $this->makeRequest('GET', "/flex/markt/$id")->toArray();
    }

    public function importTariefplan($data)
    {
        $data['file'] = DataPart::fromPath($_FILES['tarief_en_btw_import']['tmp_name']['file']);
        $formData = new FormDataPart($data);
        $options = [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ];

        return $this->makeRequest('POST', '/parse_tarief_csv', $options);
    }

    public function getRapportMeervoudigStaan(string $dag): array
    {
        return $this->makeRequest('GET', '/rapport/dubbelstaan/'.$dag)->toArray();
    }

    public function getRapportStaanverplichting(array $marktIds, string $dagStart, string $dagEind, string $vergunningType): array
    {
        return $this->makeRequest(
            'GET',
            '/rapport/staanverplichting/'.$dagStart.'/'.$dagEind.'/'.$vergunningType,
            ['query' => ['marktId' => $marktIds]]
        )->toArray();
    }

    public function getInvoerReport(int $marktId, \DateTime $dagStart, \DateTime $dagEind): array
    {
        return $this->makeRequest(
            'GET',
            '/rapport/invoer/'.$marktId.'/'.$dagStart->format('Y-m-d').'/'.$dagEind->format('Y-m-d')
        )->toArray();
    }

    public function getAanwezigheidReport(int $marktId, \DateTime $dagStart, \DateTime $dagEind): array
    {
        return $this->makeRequest(
            'GET',
            '/rapport/aanwezigheid/'.$marktId.'/'.$dagStart->format('Y-m-d').'/'.$dagEind->format('Y-m-d')
        )->toArray();
    }

    public function getRapportCapaciteit(array $marktId, \DateTime $dagStart, \DateTime $dagEind): array
    {
        return $this->makeRequest(
            'GET',
            '/rapport/marktcapaciteit',
            [
                'query' => [
                    'marktId' => $marktId,
                    'dagStart' => $dagStart->format('Y-m-d'),
                    'dagEind' => $dagEind->format('Y-m-d'),
                ],
            ]
        )->toArray();
    }

    public function getFactuurOverzicht(string $van, string $tot): array
    {
        return $this->makeRequest('GET', '/report/factuur/overzicht/'.$van.'/'.$tot)->toArray();
    }

    public function getFactuurMarktOverzicht(int $marktId, string $van, string $tot): array
    {
        return $this->makeRequest(
            'GET',
            '/report/factuur/overzichtmarkt/'.$marktId.'/'.$van.'/'.$tot,
            []
        )->toArray();
    }

    public function getRapportFactuurDetail($marktIds, $dagStart, $dagEind): array
    {
        return $this->makeRequest(
            'GET',
            '/rapport/detailfactuur',
            [
                'query' => [
                    'marktIds' => $marktIds,
                    'dagStart' => $dagStart,
                    'dagEind' => $dagEind,
                ],
            ]
        )->toArray();
    }

    public function getFrequentieReport($marktId, $type, \DateTime $dagStart, \DateTime $dagEind)
    {
        return $this->makeRequest(
            'GET',
            '/rapport/frequentie/'.$marktId.'/'.$type.'/'.$dagStart->format('Y-m-d').'/'.$dagEind->format('Y-m-d'),
        )->toArray();
    }

    public function postBtwPlan($data)
    {
        return $this->makeRequest('POST', '/btw_plan', ['json' => $data]);
    }

    public function patchBtwPlan($data)
    {
        return $this->makeRequest('PATCH', '/btw_plan/'.$data['btwPlanId'], ['json' => $data]);
    }

    public function importBtw($data)
    {
        $data['file'] = DataPart::fromPath($_FILES['tarief_en_btw_import']['tmp_name']['file']);
        $formData = new FormDataPart($data);
        $options = [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ];

        return $this->makeRequest('POST', '/parse_btw_csv', $options);
    }

    public function getBtwPlans(string $planType): array
    {
        return $this->makeRequest('GET', '/btw/plans/'.$planType)->toArray();
    }

    public function archiveBtwPlan(int $id): void
    {
        $this->makeRequest('PATCH', '/btw_plan/archive/'.$id);
    }

    public function getBtwUpdate(int $btwPlanId): array
    {
        return $this->makeRequest('GET', '/btw_plan/update/'.$btwPlanId)->toArray();
    }

    public function getBtwCreate($planType): array
    {
        return $this->makeRequest('GET', '/btw_plan/create/'.$planType)->toArray();
    }

    public function getTariefSoorten(): array
    {
        return $this->makeRequest('GET', '/tariefsoort')->toArray();
    }

    public function getDagvergunningMapping(string $type = ''): array
    {
        return $this->makeRequest('GET', '/dagvergunning_mapping', ['query' => ['type' => $type]])->toArray();
    }

    public function getDagvergunningMappingById(int $id): array
    {
        return $this->makeRequest('GET', '/dagvergunning_mapping/'.$id)->toArray();
    }

    public function updateDagvergunningMapping(int $id, array $data): void
    {
        $this->makeRequest('PUT', '/dagvergunning_mapping/'.$id, ['json' => $data]);
    }

    public function createDagvergunningMapping(array $data): void
    {
        $this->makeRequest('POST', '/dagvergunning_mapping', ['json' => $data]);
    }

    public function getFeatureFlags(): array
    {
        return $this->makeRequest('GET', '/feature_flags')->toArray();
    }

    public function createFeatureFlag($data): void
    {
        $this->makeRequest('POST', '/feature_flag', ['json' => $data]);
    }

    public function updateFeatureFlag($id, $data): void
    {
        $this->makeRequest('PATCH', "/feature_flag/$id", ['json' => $data]);
    }

    public function getFeatureFlag($id): array
    {
        return $this->makeRequest('GET', "/feature_flag/$id")->toArray();
    }

    public function getVersion(): array
    {
        return $this->makeRequest('GET', '/version/')->toArray();
    }
}
