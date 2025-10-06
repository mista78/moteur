<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Calculations Model
 *
 * @method \App\Model\Entity\Calculation newEmptyEntity()
 * @method \App\Model\Entity\Calculation newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Calculation> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Calculation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Calculation findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Calculation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Calculation> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Calculation|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Calculation saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Calculation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Calculation>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Calculation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Calculation> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Calculation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Calculation>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Calculation>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Calculation> deleteManyOrFail(iterable $entities, array $options = [])
 */
class CalculationsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config Configuration options
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('calculations');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules
     *
     * @param \Cake\Validation\Validator $validator Validator instance
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('adherent_number')
            ->maxLength('adherent_number', 50)
            ->requirePresence('adherent_number', 'create')
            ->notEmptyString('adherent_number');

        $validator
            ->scalar('statut')
            ->maxLength('statut', 10)
            ->requirePresence('statut', 'create')
            ->notEmptyString('statut')
            ->inList('statut', ['M', 'RSPM', 'CCPL'], 'Le statut doit être M, RSPM ou CCPL');

        $validator
            ->scalar('classe')
            ->maxLength('classe', 1)
            ->requirePresence('classe', 'create')
            ->notEmptyString('classe')
            ->inList('classe', ['A', 'B', 'C'], 'La classe doit être A, B ou C');

        $validator
            ->integer('option')
            ->requirePresence('option', 'create')
            ->notEmptyString('option')
            ->inList('option', [25, 50, 100], 'L\'option doit être 25, 50 ou 100');

        $validator
            ->date('birth_date')
            ->requirePresence('birth_date', 'create')
            ->notEmptyDate('birth_date');

        $validator
            ->integer('nb_jours')
            ->requirePresence('nb_jours', 'create')
            ->notEmptyString('nb_jours')
            ->greaterThanOrEqual('nb_jours', 0);

        $validator
            ->decimal('montant')
            ->requirePresence('montant', 'create')
            ->notEmptyString('montant')
            ->greaterThanOrEqual('montant', 0);

        $validator
            ->integer('age')
            ->requirePresence('age', 'create')
            ->notEmptyString('age')
            ->greaterThanOrEqual('age', 0);

        $validator
            ->integer('total_cumul_days')
            ->requirePresence('total_cumul_days', 'create')
            ->notEmptyString('total_cumul_days')
            ->greaterThanOrEqual('total_cumul_days', 0);

        $validator
            ->integer('nb_trimestres')
            ->requirePresence('nb_trimestres', 'create')
            ->notEmptyString('nb_trimestres')
            ->greaterThanOrEqual('nb_trimestres', 0);

        $validator
            ->boolean('patho_anterior')
            ->requirePresence('patho_anterior', 'create')
            ->notEmptyString('patho_anterior');

        $validator
            ->scalar('calculation_data')
            ->allowEmptyString('calculation_data');

        $validator
            ->scalar('input_data')
            ->allowEmptyString('input_data');

        $validator
            ->dateTime('calculated_at')
            ->requirePresence('calculated_at', 'create')
            ->notEmptyDateTime('calculated_at');

        return $validator;
    }

    /**
     * Find calculations by adherent number
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param string $adherentNumber Adherent number
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByAdherent(SelectQuery $query, string $adherentNumber): SelectQuery
    {
        return $query->where(['adherent_number' => $adherentNumber])
            ->orderBy(['calculated_at' => 'DESC']);
    }

    /**
     * Find recent calculations
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param int $days Number of days to look back
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findRecent(SelectQuery $query, int $days = 30): SelectQuery
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $query->where(['calculated_at >=' => $date])
            ->orderBy(['calculated_at' => 'DESC']);
    }

    /**
     * Find calculations by statut
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param string $statut Professional status
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByStatut(SelectQuery $query, string $statut): SelectQuery
    {
        return $query->where(['statut' => $statut])
            ->orderBy(['calculated_at' => 'DESC']);
    }

    /**
     * Get statistics for an adherent
     *
     * @param string $adherentNumber Adherent number
     * @return array<string, mixed>
     */
    public function getAdherentStats(string $adherentNumber): array
    {
        $calculations = $this->find()
            ->where(['adherent_number' => $adherentNumber])
            ->orderBy(['calculated_at' => 'ASC'])
            ->all()
            ->toArray();

        if (empty($calculations)) {
            return [
                'total_calculations' => 0,
                'total_amount' => 0,
                'total_days' => 0,
                'average_amount' => 0,
                'first_calculation' => null,
                'last_calculation' => null,
            ];
        }

        $totalAmount = array_sum(array_column($calculations, 'montant'));
        $totalDays = array_sum(array_column($calculations, 'nb_jours'));

        return [
            'total_calculations' => count($calculations),
            'total_amount' => $totalAmount,
            'total_days' => $totalDays,
            'average_amount' => $totalAmount / count($calculations),
            'first_calculation' => $calculations[0]->calculated_at,
            'last_calculation' => end($calculations)->calculated_at,
        ];
    }
}
