<?php

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service;

class MarktApi
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    protected $user;

    public function __construct(\GuzzleHttp\Client $client, $securityContext)
    {
        $this->client = $client;

        if ($securityContext->getToken() !== null && $securityContext->getToken()->getUser() !== null) {
            $this->user = $securityContext->getToken()->getUser();
        }
    }

    public function getAccounts()
    {
        return $this->handleResponse($this->client->request('GET', 'account/'), true);
    }

    public function getAccount($id)
    {
        $data = $this->client->request('GET', 'account/' . $id, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]);
        return $this->handleResponse($data);
    }

    public function getKoopman($id)
    {
        return $this->handleResponse($this->client->request('GET', 'koopman/id/' . $id, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]));
    }

    public function putAccount($id, $data)
    {
        return $this->handleResponse($this->client->request('PUT', 'account/' . $id, ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]));
    }

    public function postAccount($data)
    {
        return $this->handleResponse($this->client->request('POST', 'account/', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]));
    }

    public function getTariefPlan($id)
    {
        return $this->handleResponse($this->client->request('GET', 'tariefplannen/get/' . $id, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]));
    }

    public function deleteTariefPlan($id)
    {
        return $this->handleResponse($this->client->request('DELETE', 'tariefplannen/delete/' . $id, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]));
    }

    public function postLineairTariefplan($marktId, $data)
    {
        return $this->handleResponse($this->client->request('POST', 'tariefplannen/' . $marktId . '/create/lineair', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]));
    }

    public function postConcreetTariefplan($marktId, $data)
    {
        return $this->handleResponse($this->client->request('POST', 'tariefplannen/' . $marktId . '/create/concreet', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]));
    }

    public function updateLineairTariefplan($tariefPlanId, $data)
    {
        return $this->handleResponse($this->client->request('POST', 'tariefplannen/' . $tariefPlanId . '/update/lineair', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]));
    }

    public function updateConcreetTariefplan($tariefPlanId, $data)
    {
        return $this->handleResponse($this->client->request('POST', 'tariefplannen/' . $tariefPlanId . '/update/concreet', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]));
    }

    public function getMarkten()
    {
        return $this->handleResponse($this->client->request('GET', 'markt/'), true);
    }

    public function getMarkt($id)
    {
        return $this->handleResponse($this->client->request('GET', 'markt/' . $id));
    }

    public function getTariefplannenByMarktId($marktId)
    {
        return $this->handleResponse($this->client->request('GET', 'tariefplannen/list/' . $marktId, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]), true);
    }

    public function getRapportDubbelstaan($dag)
    {
        return $this->handleResponse($this->client->request('GET', 'rapport/dubbelstaan/' . $dag, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]), false);
    }

    public function getFactuurOverzicht($van, $tot)
    {
        return $this->handleResponse($this->client->request('GET', 'report/factuur/overzicht/' . $van . '/' . $tot, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]), false, $validStatusCodes = [200], $assoc = true);
    }

    public function getFactuurMarktOverzicht($marktId, $van, $tot)
    {
        return $this->handleResponse($this->client->request('GET', 'report/factuur/overzichtmarkt/' . $marktId. '/' . $van . '/' . $tot, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]), false, $validStatusCodes = [200], $assoc = true);
    }

    public function getRapportStaanverplichting($marktId, $dagStart, $dagEind, $vergunningType)
    {
        return $this->handleResponse($this->client->request('GET', 'rapport/staanverplichting/' . $marktId . '/' . $dagStart . '/' . $dagEind . '/' . $vergunningType, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]), false);
    }

    public function getDagvergunningen($filter, $listOffset, $listLength)
    {
        return $this->handleResponse($this->client->request('GET', 'dagvergunning/', ['query' => array_merge($filter, ['listOffset' => $listOffset, 'listLength' => $listLength]), 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]), true);
    }

    public function getKoopmannen($filter, $listOffset, $listLength)
    {
        return $this->handleResponse($this->client->request('GET', 'koopman/', ['query' => array_merge($filter, ['listOffset' => $listOffset, 'listLength' => $listLength]), 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]), true);
    }

    public function getVersion()
    {
        return $this->handleResponse($this->client->request('GET', 'version/'));
    }

    public function login($username, $password)
    {
        return $this->handleResponse($this->client->request('POST', 'login/basicUsername/', ['json' => ['username' => $username, 'password' => $password, 'clientApp' => 'dashboard']]));
    }

    public function getLijstenMetDatum($marktId, $types = array(), \DateTime $startDate, \DateTime $endDate) {
        return $this->handleResponse($this->client->request('GET', 'lijst/week/' . $marktId .'/' . implode('|', $types) . '/' . $startDate->format('Y-m-d') . '/' . $endDate->format('Y-m-d'), ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken()]]));
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param number[] $validStatusCodes
     * @throws \RuntimeException
     * @return mixed
     */
    private function handleResponse(\Psr\Http\Message\ResponseInterface $response, $collectionResponse = false, $validStatusCodes = [200], $assoc = false)
    {
        if (in_array($response->getStatusCode(), $validStatusCodes) === false) {
            throw new \RuntimeException('Response code of API is ' . $response->getStatusCode() . ', expected one of these [' . implode(',', $validStatusCodes) . '], response body: ' . $response->getBody());
        }

        $content = json_decode($response->getBody(), $assoc);

        if ($content === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('API error not valid JSON, JSON parser error ' . json_last_error_msg() . ', response body: ' . $response->getBody());
        }


        if ($collectionResponse === true) {
            $fullListLength = $response->getHeader('X-Api-ListSize');
            $content = [
                'fullListLength' => reset($fullListLength),
                'responseListLength' => count($content),
                'results' => $content
            ];
        }

        return $content;
    }
}