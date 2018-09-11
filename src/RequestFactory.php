<?php

declare(strict_types=1);

namespace Atoms\Http;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class RequestFactory implements RequestFactoryInterface
{
    /**
     * @var \Psr\Http\Message\UriFactoryInterface
     */
    private $uriFactory;

    /**
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * Creates a new RequestFactory instance.
     *
     * @param \Psr\Http\Message\UriFactoryInterface $uriFactory
     * @param \Psr\Http\Message\StreamFactoryInterface $streamFactory
     */
    public function __construct(
        UriFactoryInterface $uriFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->uriFactory = $uriFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (is_string($uri)) {
            $uri = $this->uriFactory->createUri($uri);
        }

        return new Request(
            $method,
            $uri,
            $this->streamFactory->createStream(''),
            [],
            '1.1'
        );
    }

    /**
     * Create a new request with a set of HTTP headers.
     *
     * @param  string $method
     * @param  UriInterface|string $uri
     * @param  array $headers
     * @return \Psr\Http\Message\RequestInterface
     */
    public function createRequestWithHeaders(string $method, $uri, array $headers): RequestInterface
    {
        if (is_string($uri)) {
            $uri = $this->uriFactory->createUri($uri);
        }

        return new Request(
            $method,
            $uri,
            $this->streamFactory->createStream(''),
            $headers,
            '1.1'
        );
    }
}
