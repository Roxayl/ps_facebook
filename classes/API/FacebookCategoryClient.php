<?php

namespace PrestaShop\Module\PrestashopFacebook\API;

use Exception;
use GuzzleHttp\Client;
use PrestaShop\Module\PrestashopFacebook\Exception\FacebookClientException;
use PrestaShop\Module\PrestashopFacebook\Factory\ApiClientFactoryInterface;
use PrestaShop\Module\PrestashopFacebook\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\Module\PrestashopFacebook\Repository\GoogleCategoryRepository;

class FacebookCategoryClient
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var GoogleCategoryRepository
     */
    private $googleCategoryRepository;

    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    public function __construct(
        ApiClientFactoryInterface $apiClientFactory,
        GoogleCategoryRepository $googleCategoryRepository,
        ErrorHandler $errorHandler
    ) {
        $this->client = $apiClientFactory->createClient();
        $this->googleCategoryRepository = $googleCategoryRepository;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param int $categoryId
     *
     * @return array|null
     *
     * @throws \PrestaShopDatabaseException
     */
    public function getGoogleCategory($categoryId)
    {
        $googleCategoryId = $this->googleCategoryRepository->getGoogleCategoryIdByCategoryId($categoryId);

        $googleCategory = $this->get('taxonomy/' . $googleCategoryId);

        if (!is_array($googleCategory)) {
            return null;
        }

        return reset($googleCategory);
    }

    protected function get($id, array $fields = [], array $query = [])
    {
        $query = array_merge(
            [
                'fields' => implode(',', $fields),
            ],
            $query
        );

        try {
            $request = $this->client->createRequest(
                'GET',
                "/{$id}",
                [
                    'query' => $query,
                ]
            );

            $response = $this->client->send($request);
        } catch (Exception $e) {
            $this->errorHandler->handle(
                new FacebookClientException(
                    'Facebook category client failed when creating get request.',
                    FacebookClientException::FACEBOOK_CLIENT_GET_FUNCTION_EXCEPTION,
                    $e
                ),
                FacebookClientException::FACEBOOK_CLIENT_GET_FUNCTION_EXCEPTION,
                false
            );

            return false;
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}