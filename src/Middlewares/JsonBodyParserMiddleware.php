<?php

declare(strict_types=1);

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware de Parsing du Corps JSON
 * Analyse le corps de requête JSON et le rend disponible comme corps analysé
 */
class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');

        // Analyser uniquement si le type de contenu est JSON
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
