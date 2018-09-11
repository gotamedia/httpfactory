<?php

declare(strict_types=1);

namespace Atoms\Http;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * @var \Atoms\Http\UriFactory
     */
    private $uriFactory;

    /**
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var \Psr\Http\Message\UploadedFileFactoryInterface
     */
    private $uploadedFileFactory;

    /**
     * Creates a new RequestFactory instance.
     *
     * @param \Atoms\Http\UriFactory $uriFactory
     * @param \Psr\Http\Message\StreamFactoryInterface $streamFactory
     * @param \Psr\Http\Message\UploadedFileFactoryInterface $uploadedFileFactory
     */
    public function __construct(
        UriFactory $uriFactory,
        StreamFactoryInterface $streamFactory,
        UploadedFileFactoryInterface $uploadedFileFactory
    ) {
        $this->uriFactory = $uriFactory;
        $this->streamFactory = $streamFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (!$uri instanceof UriInterface) {
            $uri = $this->uriFactory->createUri($uri);
        }

        return new ServerRequest(
            $method,
            $uri,
            $this->streamFactory->createStream(''),
            [],
            '1.1',
            [],
            [],
            [],
            [],
            null
        );
    }

    /**
     * Creates a new server request from server variables.
     *
     * @param  array $server Typically $_SERVER or similar structure.
     * @return \Psr\Http\Message\ServerRequestInterface
     * @throws \InvalidArgumentException
     */
    public function createServerRequestFromArray(array $server): ServerRequestInterface
    {
        $method = $this->getMethod($server);
        $uri = $this->uriFactory->createUriFromArray($server);

        return new ServerRequest(
            $method,
            $uri,
            $this->streamFactory->createStream(''),
            [],
            '1.1',
            [],
            [],
            [],
            [],
            null
        );
    }

    /**
     * Creates a new, complete server request from global server variables.
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        $serverParams = $_SERVER;
        $cookieParams = $_COOKIE;
        $queryParams = $_GET;
        $uploadedFiles = $this->normalizeFiles($_FILES);
        $parsedBody = $_POST;

        $uri = $this->uriFactory->createUriFromArray($serverParams);
        $body = $this->streamFactory->createStreamFromFile('php://input');

        $method = $this->getMethod($serverParams);
        $headers = $this->getHeaders($serverParams);
        $protocol = $this->getProtocol($serverParams);

        return new ServerRequest(
            $method,
            $uri,
            $body,
            $headers,
            $protocol,
            $serverParams,
            $cookieParams,
            $queryParams,
            $uploadedFiles,
            $parsedBody
        );
    }

    /**
     * Returns the request HTTP method.
     *
     * @param  array $server
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getMethod(array $server): string
    {
        if (!isset($server['REQUEST_METHOD'])) {
            throw new InvalidArgumentException('Cannot determine HTTP method');
        }

        return $server['REQUEST_METHOD'];
    }

    /**
     * Returns the request protocol version.
     *
     * @param  array $server
     * @return string
     */
    private function getProtocol(array $server): string
    {
        return isset($server['SERVER_PROTOCOL'])
            ? str_replace('HTTP/', '', $server['SERVER_PROTOCOL'])
            : '1.1';
    }

    /**
     * Returns an array with UploadedFile instances.
     *
     * @param  array $files
     * @return \Atoms\Http\UploadedFile[]
     * @throws \InvalidArgumentException
     */
    private function normalizeFiles(array $files): array
    {
        $normalizedFiles = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalizedFiles[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalizedFiles[$key] = $this->createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalizedFiles[$key] = $this->normalizeFiles($value);
            } else {
                throw new InvalidArgumentException('Invalid value in file specifications');
            }
        }

        return $normalizedFiles;
    }

    /**
     * Returns an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, the method will
     * delegate to normalizeNestedFileSpec() and return that value.
     *
     * @param  array $specification
     * @return \Psr\Http\Message\UploadedFileInterface|array
     */
    private function createUploadedFileFromSpec(array $specification): UploadedFileInterface
    {
        if (is_array($specification['tmp_name'])) {
            return $this->normalizeNestedFileSpec($specification);
        }

        $stream = $this->streamFactory->createStreamFromFile($specification['tmp_name']);

        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            $specification['size'],
            $specification['error'],
            $specification['name'],
            $specification['type']
        );
    }

    /**
     * Normalizes an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * UploadedFileInterface instances.
     *
     * @param  array $files
     * @return \Atoms\Http\UploadedFile[]
     */
    private function normalizeNestedFileSpec(array $files): array
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $specification = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key]
            ];

            $normalizedFiles[$key] = $this->createUploadedFileFromSpec($specification);
        }

        return $normalizedFiles;
    }

    /**
     * Returns all request headers.
     *
     * @param  array $server
     * @return array
     */
    private function getHeaders(array $server): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];

        foreach ($server as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            } elseif ($name == 'CONTENT_TYPE' || $name == 'CONTENT_LENGTH') {
                $headers[ucwords(strtolower($name), '_')] = $value;
            }
        }

        return $headers;
    }
}
