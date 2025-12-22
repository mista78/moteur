<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\AdherentInfos;
use App\Models\IjSinistre;
use App\Tools\Tools;
use Exception;
use App\IJCalculator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur Arrêts
 * Gère les endpoints liés aux arrêts de travail
 */
class ArretsController
{

    public function dateEffect(Request $request, Response $response, IJCalculator $calculator)
    {

        try {
            // Récupérer le payload JSON
            // $postArray = $request->getParsedBody();

            // Mode test - charger depuis un fichier
            $params = $request->getQueryParams();
            if (isset($params['mode']) && $params['mode'] == 'test') {
                $postArray = json_decode(
                    file_get_contents(__DIR__ . '/../../public/mock1.json'),
                    true
                );
            }

            

            

            if (empty($postArray)) {
                $response->getBody()->write(json_encode([
                    'error' => 'No data provided',
                    'data' => [],
                ]));
                return $response
                    ->withStatus(400)
                    ->withHeader('Content-Type', 'application/json');
            }

            $data = Tools::formatForOutput($calculator->calculateDateEffet(Tools::renommerCles($postArray, Tools::$correspondance), null, 0));

            return ResponseFormatter::success($response, [
                "value" => []
            ]);
        } catch (Exception $e) {
            $this->logger->error('Calculation error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

}
