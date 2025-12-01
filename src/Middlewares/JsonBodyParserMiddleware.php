<?php

declare(strict_types=1);

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * JSON Body Parser Middleware
 * Parses JSON request body and makes it available as parsed body
 */
class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');

        // Only parse if content type is JSON
        if (strpos($contentType, 'application/json') !== false) {
            $contents = (string) $request->getBody();

            if (!empty($contents)) {
                $parsed = json_decode($contents, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $request = $request->withParsedBody($parsed);
                }
            }
        }

        return $handler->handle($request);
    }
}
