<?php

/**
 * This file is part of the PHP SDK library for the Superdesk Content API.
 *
 * Copyright 2015 Sourcefabric z.u. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2015 Sourcefabric z.ú.
 * @license http://www.superdesk.org/license
 */

namespace Superdesk\ContentApiSdk;

use Superdesk\ContentApiSdk\Api\Request;
use Superdesk\ContentApiSdk\Api\Request\RequestParameters;
use Superdesk\ContentApiSdk\Api\Response;
use Superdesk\ContentApiSdk\Api\Pagerfanta\ItemAdapter;
use Superdesk\ContentApiSdk\Api\Pagerfanta\PackageAdapter;
use Superdesk\ContentApiSdk\Api\Pagerfanta\ResourceAdapter;
use Superdesk\ContentApiSdk\Api\Pagerfanta\ResourceCollection;
use Superdesk\ContentApiSdk\Client\ApiClientInterface;
use Superdesk\ContentApiSdk\Data\Item;
use Superdesk\ContentApiSdk\Data\Package;
use Superdesk\ContentApiSdk\Exception\ClientException;
use Superdesk\ContentApiSdk\Exception\ContentApiException;
use Superdesk\ContentApiSdk\Exception\InvalidArgumentException;
use Superdesk\ContentApiSdk\Exception\InvalidDataException;
use Exception;
use stdClass;

/**
 * Superdesk ContentApi class.
 */
class ContentApiSdk
{
    /**
     * Items endpoint
     */
    const SUPERDESK_ENDPOINT_ITEMS = '/items';

    /**
     * Package endpoint
     */
    const SUPERDESK_ENDPOINT_PACKAGES = '/packages';

    /**
     * Type indication for packages
     */
    const PACKAGE_TYPE_COMPOSITE = 'composite';

    /**
     * Supported API version by this SDK version
     */
    const API_VERSION = 1;

    /**
     * Useragent string sent to the API when making requests.
     */
    const USERAGENT = 'Content API SDK v1';

    /**
     * Any (http) client that implements ClientInterface.
     *
     * @var ApiClientInterface
     */
    protected $client;

    /**
     * Protocol to reach the api instance.
     *
     * @var string|null
     */
    protected $protocol = null;

    /**
     * Hostname of the api instance.
     *
     * @var string|null
     */
    protected $host = null;

    /**
     * Port of the api instance.
     *
     * @var int|null
     */
    protected $port = null;

    /**
     * Authentication object.
     *
     * @var AuthenticationInterface
     */
    protected $authentication = null;

    /**
     * Construct method for class.
     *
     * @param ApiClientInterface $client
     * @param string|null $host
     * @param int|null $port
     * @param string|null $protocol
     */
    public function __construct(
        ApiClientInterface $client,
        $host = null,
        $port = null,
        $protocol = null
    ) {
        $this->client = $client;

        if (!is_null($host)) {
            $this->setHost($host);
        }

        if (!is_null($port)) {
            $this->setPort($port);
        }

        if (!is_null($protocol)) {
            $this->setProtocol($protocol);
        }
    }

    /**
     * Gets the value of client.
     *
     * @return ApiClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the value of client.
     *
     * @param ApiClientInterface $client Value to set
     *
     * @return self
     */
    public function setClient(ApiClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Gets the value of apiHost.
     *
     * @return string|null
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the value of host.
     *
     * @param string|null $host Value to set
     *
     * @return self
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Gets the value of port.
     *
     * @return int|null
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Sets the value of port.
     *
     * @param int|null $port Value to set
     *
     * @return self
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Gets the value of protocol.
     *
     * @return string|null
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Sets the value of protocol.
     *
     * @param string|null $protocol Value to set
     *
     * @return self
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Get a single item via id.
     *
     * @param string $itemId Identifier for item
     *
     * @return Item
     */
    public function getItem($itemId)
    {
        $request = $this->getNewRequest(sprintf('%s/%s', self::SUPERDESK_ENDPOINT_ITEMS, $itemId));

        try {
            $response = $this->client->makeApiCall($request);
            $item = new Item($response->getResources());

            return $item;
        } catch (ClientException $e) {
            throw new ContentApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get multiple items based on a filter.
     *
     * @param RequestParameters $paramObj Filter parameters
     *
     * @return ResourceCollection
     */
    public function getItems(RequestParameters $paramObj)
    {
        return $this->getNewResourceCollection(
            new ItemAdapter(
                $this->client,
                $this->getNewRequest(self::SUPERDESK_ENDPOINT_ITEMS, $paramObj)
            )
        );
    }

    /**
     * Get package by identifier.
     *
     * @param string $packageId Package identifier
     * @param bool   $resolveAssociations Inject full associations recursively
     *                                    instead of references by uri.
     *
     * @return Package
     */
    public function getPackage($packageId, $resolveAssociations = false)
    {
        $request = $this->getNewRequest(sprintf('%s/%s', self::SUPERDESK_ENDPOINT_PACKAGES, $packageId));
        $response = $this->client->makeApiCall($request);

        $package = new Package($response->getResources());

        // This can be removed once the API fully supports retrieving package associations
        if ($resolveAssociations) {
            $associations = $this->getAssociationsFromPackage($package);
            $package = $this->injectAssociations($package, $associations);
        }

        return $package;
    }

    /**
     * Get multiple packages based on a filter.
     *
     * @param RequestParameters $paramObj Filter parameters
     * @param bool $resolveAssociations Inject full associations recursively
     *                                  instead of references by uri.
     *
     * @return ResourceCollection
     */
    public function getPackages(
        RequestParameters $paramObj,
        $resolveAssociations = false
    ) {
        return $this->getNewResourceCollection(
            new PackageAdapter(
                $this->client,
                $this->getNewRequest(self::SUPERDESK_ENDPOINT_PACKAGES, $paramObj),
                $this,
                $resolveAssociations
            )
        );
    }

    /**
     * Gets full objects for all associations for a package.
     *
     * @param Package $package A package
     *
     * @return stdClass List of associations
     */
    public function getAssociationsFromPackage(Package $package)
    {
        $associations = new stdClass();

        if (property_exists($package, 'associations')) {

            foreach ($package->associations as $associationGroupName => $associationGroupItems) {

                $groupAssociations = new stdClass();

                foreach ($associationGroupItems as $associatedName => $associatedItem) {
                    $associatedId = $this->getIdFromUri($associatedItem->uri);

                    try {
                        if ($associatedItem->type == self::PACKAGE_TYPE_COMPOSITE) {
                            $associatedObj = $this->getPackage($associatedId, true);
                        } else {
                            $associatedObj = $this->getItem($associatedId);
                            $associatedObj->type = $associatedItem->type;
                        }
                    } catch (ContentApiException $e) {
                        // If subrequests fail, dont fail main request
                    }

                    $groupAssociations->$associatedName = $associatedObj;
                }

                $associations->$associationGroupName = $groupAssociations;
            }
        }

        return $associations;
    }

    /**
     * Overwrite the associations links in a packages with the actual association
     * data.
     *
     * @param Package  $package      Package
     * @param stdClass $associations Multiple items or packages
     *
     * @return Package Package with data injected
     */
    public function injectAssociations(Package $package, stdClass $associations)
    {
        if (count($package->associations) > 0 && count($associations) > 0) {
            $package->associations = $associations;
        }

        return $package;
    }

    /**
     * Shortcut method to create new class.
     *
     * @param  string $uri Uri of the request
     * @param  RequestParameters|null $parameters Parameters for the request
     *                                            object
     *
     * @return Request
     */
    public function getNewRequest($uri, RequestParameters $parameters = null)
    {
        try {
            $request = new Request($this->host, $uri, $parameters, $this->port, $this->protocol);
        } catch (ContentApiException $e) {
            throw new ContentApiException($e->getMessage(), $e->getCode(), $e);
        }

        return $request;
    }

    /**
     * Shortcut to get a new ResourceCollection object.
     *
     * @param  ResourceAdapter $resourceAdapter
     *
     * @return ResourceCollection
     */
    private function getNewResourceCollection(ResourceAdapter $resourceAdapter)
    {
        return new ResourceCollection($resourceAdapter);
    }

    /**
     * Tries to find a valid id in an uri, both item as package uris. The id
     * is returned urldecoded.
     *
     * @param string $uri Item or package uri
     *
     * @return string Urldecoded id
     */
    public static function getIdFromUri($uri)
    {
        /*
         * Works for package and item uris
         *   http://publicapi:5050/packages/tag%3Ademodata.org%2C0012%3Aninjs_XYZ123
         *   http://publicapi:5050/items/tag%3Ademodata.org%2C0003%3Aninjs_XYZ123
         */

        $uriPath = parse_url($uri, PHP_URL_PATH);
        $objectId = str_replace(self::getAvailableEndpoints(), '', $uriPath);
        // Remove possible slashes and spaces, since we're working with urls
        $objectId = trim($objectId, '/ ');
        $objectId = urldecode($objectId);

        return $objectId;
    }

    /**
     * Returns a list of all supported endpoints for the Superdesk Content API.
     *
     * @return string[]
     */
    public static function getAvailableEndpoints()
    {
        return array(
            self::SUPERDESK_ENDPOINT_ITEMS,
            self::SUPERDESK_ENDPOINT_PACKAGES,
        );
    }

    /**
     * Converts json string into StdClass object. Throws an InvalidDataException
     * when string could not be converted to object.
     *
     * @param string $jsonString JSON string
     *
     * @return object
     * @throws Exception|InvalidDataException
     */
    public static function getValidJsonObj($jsonString)
    {
        $jsonObj = json_decode($jsonString);
        if (is_null($jsonObj) || json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidDataException('Response body is not (valid) json.', json_last_error());
        }

        return $jsonObj;
    }

    /**
     * Returns version of api for creating verioned url.
     *
     * @return string
     */
    public static function getVersionURL()
    {
        return sprintf('v%d', self::API_VERSION);
    }
}
