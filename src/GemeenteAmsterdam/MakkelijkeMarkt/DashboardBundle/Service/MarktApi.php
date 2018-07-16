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

namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Service;

class MarktApi
{
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    protected $user;

    /**
     * @var string
     */
    protected $mmAppKey;

    public function __construct(\GuzzleHttp\Client $client, $securityContext, $mmAppKey)
    {
        $this->client = $client;
        $this->mmAppKey = $mmAppKey;

        if ($securityContext->getToken() !== null && $securityContext->getToken()->getUser() !== null) {
            $this->user = $securityContext->getToken()->getUser();
        }
    }

    public function getAccounts($active = -1, $locked = -1)
    {
        return $this->handleResponse($this->client->request('GET', 'account/', ['query' => ['active' => $active, 'locked' => $locked], 'headers' => ['MmAppKey' => $this->mmAppKey]]), true);
    }

    public function getBtw()
    {
        return $this->handleResponse($this->client->request('GET', 'btw/', ['headers' => ['MmAppKey' => $this->mmAppKey]]), true);
    }

    public function postBtw($data)
    {
        return $this->handleResponse($this->client->request('POST', 'btw/', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function getAccount($id)
    {
        $data = $this->client->request('GET', 'account/' . $id, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]);
        return $this->handleResponse($data);
    }

    public function getKoopman($id)
    {
        return $this->handleResponse($this->client->request('GET', 'koopman/id/' . $id, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function putAccount($id, $data)
    {
        return $this->handleResponse($this->client->request('PUT', 'account/' . $id, ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function postAccount($data)
    {
        return $this->handleResponse($this->client->request('POST', 'account/', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function getTariefPlan($id)
    {
        return $this->handleResponse($this->client->request('GET', 'tariefplannen/get/' . $id, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function deleteTariefPlan($id)
    {
        return $this->handleResponse($this->client->request('DELETE', 'tariefplannen/delete/' . $id, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function postLineairTariefplan($marktId, $data)
    {
        return $this->handleResponse($this->client->request('POST', 'tariefplannen/' . $marktId . '/create/lineair', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function postConcreetTariefplan($marktId, $data)
    {
        return $this->handleResponse($this->client->request('POST', 'tariefplannen/' . $marktId . '/create/concreet', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function updateLineairTariefplan($tariefPlanId, $data)
    {
        return $this->handleResponse($this->client->request('POST', 'tariefplannen/' . $tariefPlanId . '/update/lineair', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function updateConcreetTariefplan($tariefPlanId, $data)
    {
        return $this->handleResponse($this->client->request('POST', 'tariefplannen/' . $tariefPlanId . '/update/concreet', ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function getMarkten()
    {
        return $this->handleResponse($this->client->request('GET', 'markt/', ['headers' => ['MmAppKey' => $this->mmAppKey]]), true);
    }

    public function getMarkt($id)
    {
        return $this->handleResponse($this->client->request('GET', 'markt/' . $id, ['headers' => ['MmAppKey' => $this->mmAppKey]]));
    }

    public function postMarkt($id, $data)
    {
        return $this->handleResponse($this->client->request('POST', 'markt/' . $id, ['json' => $data, 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function getTariefplannenByMarktId($marktId)
    {
        return $this->handleResponse($this->client->request('GET', 'tariefplannen/list/' . $marktId, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), true);
    }

    public function getRapportDubbelstaan($dag)
    {
        return $this->handleResponse($this->client->request('GET', 'rapport/dubbelstaan/' . $dag, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), false);
    }

    public function getFactuurOverzicht($van, $tot)
    {
        return $this->handleResponse($this->client->request('GET', 'report/factuur/overzicht/' . $van . '/' . $tot, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), false, $validStatusCodes = [200], $assoc = true);
    }

    public function getFactuurMarktOverzicht($marktId, $van, $tot)
    {
        return $this->handleResponse($this->client->request('GET', 'report/factuur/overzichtmarkt/' . $marktId. '/' . $van . '/' . $tot, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), false, $validStatusCodes = [200], $assoc = true);
    }

    public function getRapportStaanverplichting($marktId, $dagStart, $dagEind, $vergunningType)
    {
        return $this->handleResponse($this->client->request('GET', 'rapport/staanverplichting/' . $marktId . '/' . $dagStart . '/' . $dagEind . '/' . $vergunningType, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), false);
    }

    public function getRapportFactuurDetail($marktIds, $dagStart, $dagEind)
    {
        return $this->handleResponse($this->client->request('GET', 'rapport/detailfactuur', ['query' => ['marktIds' => $marktIds, 'dagStart' => $dagStart, 'dagEind' => $dagEind], 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), false);
    }

    public function getRapportCapaciteit($marktId, $dagStart, $dagEind)
    {
        return $this->handleResponse($this->client->request('GET', 'rapport/marktcapaciteit', ['query' => ['marktId' => $marktId, 'dagStart' => $dagStart, 'dagEind' => $dagEind], 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), false);
    }

    public function getDagvergunningen($filter, $listOffset, $listLength)
    {
        return $this->handleResponse($this->client->request('GET', 'dagvergunning/', ['query' => array_merge($filter, ['listOffset' => $listOffset, 'listLength' => $listLength]), 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), true);
    }

    public function getDagvergunningenByDate($koopmanId, \DateTime $startDate, \DateTime $endDate)
    {
        return $this->handleResponse($this->client->request('GET', 'dagvergunning_by_date/' . $koopmanId . '/' . $startDate->format('Y-m-d') . '/' . $endDate->format('Y-m-d') , ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), true);
    }

    public function getKoopmannen($filter, $listOffset, $listLength)
    {
        return $this->handleResponse($this->client->request('GET', 'koopman/', ['query' => array_merge($filter, ['listOffset' => $listOffset, 'listLength' => $listLength]), 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), true);
    }

    public function getFrequentieReport($marktId, $type, \DateTime $dagStart, \DateTime $dagEind)
    {
        return $this->handleResponse($this->client->request('GET', 'rapport/frequentie/' . $marktId . '/' . $type . '/' . $dagStart->format('Y-m-d') . '/' . $dagEind->format('Y-m-d'), ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), false, $validStatusCodes = [200], $assoc = true);

    }

    public function getAanwezigheidReport($marktId, \DateTime $dagStart, \DateTime $dagEind)
    {
        return $this->handleResponse($this->client->request('GET', 'rapport/aanwezigheid/' . $marktId . '/' . $dagStart->format('Y-m-d') . '/' . $dagEind->format('Y-m-d'), ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), false, $validStatusCodes = [200], $assoc = true);

    }

    public function getInvoerReport($marktId, \DateTime $dagStart, \DateTime $dagEind)
    {
        return $this->handleResponse($this->client->request('GET', 'rapport/invoer/' . $marktId . '/' . $dagStart->format('Y-m-d') . '/' . $dagEind->format('Y-m-d'), ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), false, $validStatusCodes = [200], $assoc = true);

    }

    public function getVersion()
    {
        return $this->handleResponse($this->client->request('GET', 'version/', ['headers' => ['MmAppKey' => $this->mmAppKey]]));
    }

    public function login($username, $password)
    {
        return $this->handleResponse($this->client->request('POST', 'login/basicUsername/', ['json' => ['username' => $username, 'password' => $password, 'clientApp' => 'dashboard'], 'headers' => ['MmAppKey' => $this->mmAppKey]]));
    }

    public function getLijstenMetDatum($marktId, $types = array(), \DateTime $startDate, \DateTime $endDate) {
        return $this->handleResponse($this->client->request('GET', 'lijst/week/' . $marktId .'/' . implode('|', $types) . '/' . $startDate->format('Y-m-d') . '/' . $endDate->format('Y-m-d'), ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function unlockAccount($accountId)
    {
        return $this->handleResponse($this->client->request('POST', 'account/unlock/' . $accountId, ['headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]));
    }

    public function getTokensByAccount($accountId, $listOffset, $listLength)
    {
        return $this->handleResponse($this->client->request('GET', 'account/' . $accountId . '/tokens', ['query' => ['listOffset' => $listOffset, 'listLength' => $listLength], 'headers' => ['Authorization' => 'Bearer ' . $this->user->getToken(), 'MmAppKey' => $this->mmAppKey]]), true);
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