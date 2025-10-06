<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\IJCalculatorService;
use Cake\Http\Response;

/**
 * Indemnite Journaliere Controller
 *
 * Gère les calculs d'indemnités journalières pour les professionnels de santé
 */
class IndemniteJournaliereController extends AppController
{
    /**
     * Calculate IJ amount
     *
     * Endpoint: POST /indemnite-journaliere/calculate
     *
     * @return \Cake\Http\Response|null Renders view
     */
    public function calculate(): ?Response
    {
        $this->request->allowMethod(['post', 'get']);

        $result = null;
        $errors = null;

        if ($this->request->is('post')) {
            try {
                // Charger le service
                $csvPath = CONFIG . 'taux.csv';
                $calculator = new IJCalculatorService($csvPath);

                // Préparer les données depuis le formulaire
                $data = [
                    'arrets' => $this->request->getData('arrets', []),
                    'statut' => $this->request->getData('statut'),
                    'classe' => $this->request->getData('classe'),
                    'option' => $this->request->getData('option', 100),
                    'birth_date' => $this->request->getData('birth_date'),
                    'current_date' => $this->request->getData('current_date', date('Y-m-d')),
                    'attestation_date' => $this->request->getData('attestation_date'),
                    'last_payment_date' => $this->request->getData('last_payment_date'),
                    'affiliation_date' => $this->request->getData('affiliation_date'),
                    'nb_trimestres' => (int)$this->request->getData('nb_trimestres', 0),
                    'previous_cumul_days' => (int)$this->request->getData('previous_cumul_days', 0),
                    'prorata' => (float)$this->request->getData('prorata', 1),
                    'patho_anterior' => (bool)$this->request->getData('patho_anterior', false),
                    'first_pathology_stop_date' => $this->request->getData('first_pathology_stop_date'),
                    'historical_reduced_rate' => $this->request->getData('historical_reduced_rate'),
                ];

                // Calculer le montant
                $result = $calculator->calculateAmount($data);

                // Optionnel: Sauvegarder dans la base de données
                if ($this->request->getData('save')) {
                    $this->saveCalculation($result, $data);
                }

                $this->Flash->success(__('Calcul effectué avec succès. Montant: {0} €', number_format($result['montant'], 2, ',', ' ')));
            } catch (\Exception $e) {
                $errors = $e->getMessage();
                $this->Flash->error(__('Erreur lors du calcul: {0}', $e->getMessage()));
            }
        }

        $this->set(compact('result', 'errors'));
    }

    /**
     * Calculate API endpoint for JSON requests
     *
     * Endpoint: POST /indemnite-journaliere/api-calculate.json
     *
     * @return void
     */
    public function apiCalculate(): void
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setClassName('Json');

        try {
            $csvPath = CONFIG . 'taux.csv';
            $calculator = new IJCalculatorService($csvPath);

            $data = $this->request->getData();
            $result = $calculator->calculateAmount($data);

            $this->set([
                'success' => true,
                'data' => $result,
                '_serialize' => ['success', 'data'],
            ]);
        } catch (\Exception $e) {
            $this->response = $this->response->withStatus(400);
            $this->set([
                'success' => false,
                'error' => $e->getMessage(),
                '_serialize' => ['success', 'error'],
            ]);
        }
    }

    /**
     * Index page - Formulaire de calcul
     *
     * @return void
     */
    public function index(): void
    {
        // Set the custom layout
        $this->viewBuilder()->setLayout('ij');

        // Liste des statuts disponibles
        $statuts = [
            'M' => 'Médecin',
            'RSPM' => 'Régime Spécial Professions Médicales',
            'CCPL' => 'Contrat Complémentaire',
        ];

        // Liste des classes
        $classes = [
            'A' => 'Classe A',
            'B' => 'Classe B',
            'C' => 'Classe C',
        ];

        // Options disponibles
        $options = [
            25 => '25%',
            50 => '50%',
            100 => '100%',
        ];

        $this->set(compact('statuts', 'classes', 'options'));
    }

    /**
     * View calculation details
     *
     * @param int|null $id Calculation ID
     * @return void
     */
    public function view(?int $id = null): void
    {
        // Si vous avez une table Calculations
        // $calculation = $this->Calculations->get($id);
        // $this->set(compact('calculation'));

        $this->set('id', $id);
    }

    /**
     * Save calculation to database (optionnel)
     *
     * @param array<string, mixed> $result Calculation result
     * @param array<string, mixed> $data Input data
     * @return bool
     */
    private function saveCalculation(array $result, array $data): bool
    {
        // Si vous avez une table Calculations
        /*
        $calculation = $this->Calculations->newEntity([
            'statut' => $data['statut'],
            'classe' => $data['classe'],
            'birth_date' => $data['birth_date'],
            'nb_jours' => $result['nb_jours'],
            'montant' => $result['montant'],
            'age' => $result['age'],
            'total_cumul_days' => $result['total_cumul_days'],
            'calculation_data' => json_encode($result),
            'input_data' => json_encode($data),
            'calculated_at' => new \DateTime(),
        ]);

        return (bool)$this->Calculations->save($calculation);
        */

        return true;
    }
}
