<?php

declare(strict_types=1);

namespace Atoms\HttpFactory;

use Atoms\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response(
            $code,
            $reasonPhrase,
            (new StreamFactory())->createStream(''),
            [],
            '1.1'
        );
    }
}
