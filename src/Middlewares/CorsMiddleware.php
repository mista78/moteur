<?php

declare(strict_types=1);

namespace App\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CORS Middleware
 * Handles Cross-Origin Resource Sharing headers
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            $response = new \Slim\Psr7\Response();
            return $this->addCorsHeaders($response);
        }

        // Process the request
        $response = $handler->handle($request);

        // Add CORS headers to the response
        return $this->addCorsHeaders($response);
    }

    /**
     * Add CORS headers to response
     *
     * Configuration complète pour accepter toutes les requêtes cross-origin
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    private function addCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            // Accepte toutes les origines
            ->withHeader('Access-Control-Allow-Origin', '*')

            // Accepte toutes les méthodes HTTP
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD')

            // Accepte tous les headers personnalisés
            ->withHeader('Access-Control-Allow-Headers', '*')

            // Expose tous les headers dans la réponse
            ->withHeader('Access-Control-Expose-Headers', '*')

            // Cache les résultats preflight pendant 24 heures
            ->withHeader('Access-Control-Max-Age', '86400')

            // Permet les credentials (cookies, authorization headers)
            // Note: Avec Allow-Origin: *, Allow-Credentials doit être omis
            // ->withHeader('Access-Control-Allow-Credentials', 'true')

            // Headers additionnels pour compatibilité
            ->withHeader('Vary', 'Origin');
    }
}
