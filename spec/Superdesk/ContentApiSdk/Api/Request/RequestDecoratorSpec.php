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

namespace spec\Superdesk\ContentApiSdk\Api\Request;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Superdesk\ContentApiSdk\Api\Request;
use Superdesk\ContentApiSdk\Api\Request\RequestInterface;
use Superdesk\ContentApiSdk\Api\Request\RequestParameters;

class RequestDecoratorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Superdesk\ContentApiSdk\Api\Request\RequestDecorator');
    }

    function let()
    {
        $requestParams = new RequestParameters();
        $requestParams->setQuery('text')->setMaxResults(10);
        $request = new Request(
            'example.com',
            '/request/uri',
            $requestParams,
            100
        );
        $request->setHeaders(array(
            'Content-Type' => 'application/json'
        ));
        $request->setOptions(array(
            'some_options' => 'some value'
        ));
        $this->beConstructedWith($request);
    }

    function it_should_return_the_host_of_the_decorated_request()
    {
        $this->getHost()->shouldReturn('example.com');
    }

    function it_should_return_the_port_of_the_decorated_request()
    {
        $this->getPort()->shouldReturn(100);
    }

    function it_should_return_the_uri_of_the_decorated_request()
    {
        $this->getUri()->shouldReturn('/request/uri');
    }

    function it_should_return_the_parameters_of_the_decorated_request()
    {
        $this->getParameters()->shouldHaveType('\Superdesk\ContentApiSdk\Api\Request\RequestParameters');
    }

    function it_should_return_the_headers_of_the_decorated_request()
    {
        $this->getHeaders()->shouldReturn(array(
            'Content-Type' => 'application/json'
        ));
    }

    function it_should_return_the_options_of_the_decorated_request()
    {
        $this->getOptions()->shouldReturn(array(
            'some_options' => 'some value'
        ));
    }

    function it_should_return_the_base_url_of_the_decorated_request()
    {
        $this->getBaseUrl()->shouldBe('https://example.com:100');
    }

    function it_should_return_the_full_url_of_the_decorated_request()
    {
        $this->getFullUrl()->shouldBe('https://example.com:100/request/uri?q=text&page=1&max_results=10');
    }
}
