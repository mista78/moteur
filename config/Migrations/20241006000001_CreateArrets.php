<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateArrets extends AbstractMigration
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
        $table = $this->table('arrets');

        $table
            ->addColumn('arret_from', 'date', [
                'default' => null,
                'null' => false,
                'comment' => 'Date de début de l\'arrêt',
            ])
            ->addColumn('arret_to', 'date', [
                'default' => null,
                'null' => false,
                'comment' => 'Date de fin de l\'arrêt',
            ])
            ->addColumn('arret_diff', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'Nombre de jours d\'arrêt',
            ])
            ->addColumn('attestation_date', 'date', [
                'default' => null,
                'null' => true,
                'comment' => 'Date d\'attestation',
            ])
            ->addColumn('declaration_date', 'date', [
                'default' => null,
                'null' => true,
                'comment' => 'Date de déclaration',
            ])
            ->addColumn('rechute', 'integer', [
                'default' => 0,
                'limit' => 11,
                'null' => false,
                'comment' => 'Indicateur de rechute (0, 1, 15)',
            ])
            ->addColumn('option', 'integer', [
                'default' => 100,
                'limit' => 11,
                'null' => false,
                'comment' => 'Option de cotisation (25, 50, 100)',
            ])
            ->addColumn('date_effet', 'date', [
                'default' => null,
                'null' => true,
                'comment' => 'Date d\'effet des droits',
            ])
            ->addColumn('code_pathologie', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'comment' => 'Code pathologie',
            ])
            ->addColumn('adherent_number', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
                'comment' => 'Numéro d\'adhérent',
            ])
            ->addColumn('birth_date', 'date', [
                'default' => null,
                'null' => false,
                'comment' => 'Date de naissance',
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
            ->addIndex(['arret_from'])
            ->addIndex(['arret_to'])
            ->addIndex(['code_pathologie'])
            ->create();
    }
}
