<?php

namespace TheP6\ILLocationFetcher\CKANFetcher;

use InvalidArgumentException;
use TheP6\ILLocationFetcher\Exceptions\InvalidCKANResponse;

class CKANFetcher
{
    private ?string $ckanServer = null;

    private ?string $dataStoreResourceId = null;

    public function setCKANServer(string $ckanServer): self {
        $this->ckanServer = $ckanServer;
        return $this;
    }

    public function setDataStoreResourceId(string $resourceId): self
    {
        $this->dataStoreResourceId = $resourceId;
        return $this;
    }

    public function fetchRecords(array $params = []): array
    {
        $url = $this->formRequestUrl($params);

        $response = $this->makeRequest($url);
        $response = $this->decodeResponse($response);
        $this->checkCkanResponse($response);

        return $response['result']['records'] ?? [];
    }

    private function formRequestUrl(array $params = []) {

        if (null === $this->ckanServer) {
            throw new InvalidArgumentException("CKANServer is null!");
        }

        if (null === $this->dataStoreResourceId) {
            throw new InvalidArgumentException("Resource id is null!");
        }

        $url = "{$this->ckanServer}/api/3/action/datastore_search?resource_id={$this->dataStoreResourceId}";

        $allowedKeys = ['limit', 'offset', 'sort'];

        $params = array_filter($params, function ($key) use ($allowedKeys) {
            return in_array($key, $allowedKeys);
        }, ARRAY_FILTER_USE_KEY);

        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }

        return $url;
    }

    private function makeRequest($url, $makePost = false): string
    {
        $ch =  curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        if ($makePost) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $makePost);
        }

        $result =  curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($responseCode > 200) {
            throw new InvalidCKANResponse("HTTP Request failed: received response code: {$responseCode} instead of 200");
        }

        return $result ?? '';
    }

    public function decodeResponse(string $response): array
    {
        $decoded = json_decode($response, true);

        if (FALSE === $decoded) {
            throw new InvalidCKANResponse("Response is not json!");
        }

        return $decoded;
    }

    private function checkCkanResponse(array $response)
    {
        if (empty($response['success'])) {
            throw new InvalidCKANResponse("Response is not successful!");
        }

        if (!key_exists('result', $response)) {
            throw new InvalidCKANResponse("Result is not provided!");
        }

        if (!key_exists('records', $response['result'])) {
            throw new InvalidCKANResponse("Records are not provided!");
        }
    }
}