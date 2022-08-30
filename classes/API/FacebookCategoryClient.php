<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\PrestashopFacebook\API;

use Exception;
use GuzzleHttp\Psr7\Request;
use PrestaShop\Module\PrestashopFacebook\Exception\FacebookClientException;
use PrestaShop\Module\PrestashopFacebook\Factory\ApiClientFactoryInterface;
use PrestaShop\Module\PrestashopFacebook\Handler\ErrorHandler\ErrorHandler;
use PrestaShop\Module\PrestashopFacebook\Repository\GoogleCategoryRepository;
use Psr\Http\Client\ClientInterface;

class FacebookCategoryClient
{
    /**
     * @var ClientInterface
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
     * @param int $shopId
     *
     * @return array|null
     */
    public function getGoogleCategory($categoryId, $shopId)
    {
        $googleCategoryId = $this->googleCategoryRepository->getGoogleCategoryIdByCategoryId($categoryId, $shopId);

        if (empty($googleCategoryId)) {
            return null;
        }

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
            $request = new Request(
                'GET',
                "/{$id}",
                [
                    'query' => $query,
                ]
            );

            $response = $this->client->sendRequest($request);
            $responseContent = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() >= 400) {
                // TODO: Error sent to the error handler can be improved from the response content
                $this->errorHandler->handle(
                    new FacebookClientException(
                        'Facebook category client failed when creating get request.',
                        FacebookClientException::FACEBOOK_CLIENT_GET_FUNCTION_EXCEPTION
                    ),
                    $response->getStatusCode(),
                    false,
                    [
                        'extra' => $responseContent,
                    ]
                );

                return false;
            }
        } catch (Exception $e) {
            $this->errorHandler->handle(
                new FacebookClientException(
                    'Facebook category client failed when creating get request.',
                    FacebookClientException::FACEBOOK_CLIENT_GET_FUNCTION_EXCEPTION,
                    $e
                ),
                $e->getCode(),
                false
            );

            return false;
        }

        return $responseContent;
    }
}
