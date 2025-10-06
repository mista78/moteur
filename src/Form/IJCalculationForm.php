<?php
declare(strict_types=1);

namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * IJ Calculation Form
 *
 * Formulaire pour le calcul des indemnités journalières
 */
class IJCalculationForm extends Form
{
    /**
     * Builds the schema for the form
     *
     * @param \Cake\Form\Schema $schema Schema to modify
     * @return \Cake\Form\Schema
     */
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema
            ->addField('adherent_number', ['type' => 'string'])
            ->addField('statut', ['type' => 'string'])
            ->addField('classe', ['type' => 'string'])
            ->addField('option', ['type' => 'integer'])
            ->addField('birth_date', ['type' => 'date'])
            ->addField('current_date', ['type' => 'date'])
            ->addField('attestation_date', ['type' => 'date'])
            ->addField('last_payment_date', ['type' => 'date'])
            ->addField('affiliation_date', ['type' => 'date'])
            ->addField('nb_trimestres', ['type' => 'integer'])
            ->addField('previous_cumul_days', ['type' => 'integer'])
            ->addField('prorata', ['type' => 'decimal'])
            ->addField('patho_anterior', ['type' => 'boolean'])
            ->addField('first_pathology_stop_date', ['type' => 'date'])
            ->addField('historical_reduced_rate', ['type' => 'string'])
            ->addField('arrets', ['type' => 'array']);
    }

    /**
     * Form validation builder
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('adherent_number')
            ->notEmptyString('adherent_number', 'Le numéro d\'adhérent est requis')
            ->maxLength('adherent_number', 50);

        $validator
            ->scalar('statut')
            ->notEmptyString('statut', 'Le statut est requis')
            ->inList('statut', ['M', 'RSPM', 'CCPL'], 'Le statut doit être M, RSPM ou CCPL');

        $validator
            ->scalar('classe')
            ->notEmptyString('classe', 'La classe est requise')
            ->inList('classe', ['A', 'B', 'C'], 'La classe doit être A, B ou C');

        $validator
            ->integer('option')
            ->notEmptyString('option', 'L\'option est requise')
            ->inList('option', [25, 50, 100], 'L\'option doit être 25, 50 ou 100');

        $validator
            ->date('birth_date')
            ->notEmptyDate('birth_date', 'La date de naissance est requise');

        $validator
            ->date('current_date')
            ->allowEmptyDate('current_date');

        $validator
            ->date('attestation_date')
            ->allowEmptyDate('attestation_date');

        $validator
            ->date('last_payment_date')
            ->allowEmptyDate('last_payment_date');

        $validator
            ->date('affiliation_date')
            ->allowEmptyDate('affiliation_date');

        $validator
            ->integer('nb_trimestres')
            ->allowEmptyString('nb_trimestres')
            ->greaterThanOrEqual('nb_trimestres', 0, 'Le nombre de trimestres doit être positif');

        $validator
            ->integer('previous_cumul_days')
            ->allowEmptyString('previous_cumul_days')
            ->greaterThanOrEqual('previous_cumul_days', 0, 'Le cumul précédent doit être positif');

        $validator
            ->decimal('prorata')
            ->allowEmptyString('prorata')
            ->greaterThan('prorata', 0, 'Le prorata doit être supérieur à 0')
            ->lessThanOrEqual('prorata', 1, 'Le prorata ne peut pas dépasser 1');

        $validator
            ->boolean('patho_anterior')
            ->allowEmptyString('patho_anterior');

        $validator
            ->date('first_pathology_stop_date')
            ->allowEmptyDate('first_pathology_stop_date');

        $validator
            ->scalar('historical_reduced_rate')
            ->allowEmptyString('historical_reduced_rate');

        $validator
            ->isArray('arrets', 'Les arrêts doivent être fournis sous forme de tableau')
            ->notEmptyArray('arrets', 'Au moins un arrêt doit être fourni');

        return $validator;
    }

    /**
     * Execute the form if it is valid
     *
     * @param array<string, mixed> $data Form data
     * @return bool
     */
    protected function _execute(array $data): bool
    {
        // La logique d'exécution sera gérée par le controller
        // Ce formulaire sert uniquement à la validation
        return true;
    }
}
