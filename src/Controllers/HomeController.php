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
use App\Models\ZDFCAIJNG;

/**
 * Contrôleur Home
 * Gère les endpoints de test et débogage
 */
class HomeController
{

    private LoggerInterface $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function index(Request $request, Response $response, IJCalculator $calculator)
    {
        // dd(AdherentInfos::limit(1)->get()->toArray());
        // dd(IjSinistre::with(['recapIndems', 'recaps'])->where("id", "23405")->get()->toArray());

        try {
            // Récupérer le payload JSON
            $postArray = $request->getParsedBody();

            // Mode test - charger depuis un fichier
            $params = $request->getQueryParams();
            if (isset($params['mode']) && $params['mode'] == 'test') {
                $postArray = json_decode(
                    file_get_contents(__DIR__ . '/../../public/mock_step.json'),
                    true
                );
            }



            // 1. Insérer le sinistre (crée automatiquement le CAR)
            ZDFCAIJNG::insertSinistre(8038);

            dd($calculator->calculateAmount($postArray));

            

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
                "value" => $data
            ]);
        } catch (Exception $e) {
            $this->logger->error('Calculation error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

}
