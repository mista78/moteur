<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateCalculations extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('calculations');

        $table
            ->addColumn('adherent_number', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
                'comment' => 'Numéro d\'adhérent',
            ])
            ->addColumn('statut', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'comment' => 'Statut (M, RSPM, CCPL)',
            ])
            ->addColumn('classe', 'string', [
                'default' => null,
                'limit' => 1,
                'null' => false,
                'comment' => 'Classe (A, B, C)',
            ])
            ->addColumn('option', 'integer', [
                'default' => 100,
                'limit' => 11,
                'null' => false,
                'comment' => 'Option de cotisation (25, 50, 100)',
            ])
            ->addColumn('birth_date', 'date', [
                'default' => null,
                'null' => false,
                'comment' => 'Date de naissance',
            ])
            ->addColumn('nb_jours', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'Nombre de jours indemnisables',
            ])
            ->addColumn('montant', 'decimal', [
                'default' => null,
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'comment' => 'Montant calculé',
            ])
            ->addColumn('age', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'Age lors du calcul',
            ])
            ->addColumn('total_cumul_days', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'Total jours cumulés',
            ])
            ->addColumn('nb_trimestres', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'Nombre de trimestres',
            ])
            ->addColumn('patho_anterior', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'Pathologie antérieure',
            ])
            ->addColumn('calculation_data', 'text', [
                'default' => null,
                'null' => true,
                'comment' => 'Données de calcul (JSON)',
            ])
            ->addColumn('input_data', 'text', [
                'default' => null,
                'null' => true,
                'comment' => 'Données d\'entrée (JSON)',
            ])
            ->addColumn('calculated_at', 'datetime', [
                'default' => null,
                'null' => false,
                'comment' => 'Date du calcul',
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addIndex(['adherent_number'])
            ->addIndex(['statut'])
            ->addIndex(['calculated_at'])
            ->addIndex(['birth_date'])
            ->create();
    }
}
