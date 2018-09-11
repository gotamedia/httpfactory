<?php

declare(strict_types=1);

namespace Atoms\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * Creates a new ResponseFactory instance.
     *
     * @param \Psr\Http\Message\StreamFactoryInterface $streamFactory
     */
    public function __construct(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response(
            $code,
            $reasonPhrase,
            $this->streamFactory->createStream(''),
            [],
            '1.1'
        );
    }
}
