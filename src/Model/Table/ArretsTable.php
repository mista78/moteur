<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Arrets Model
 *
 * @method \App\Model\Entity\Arret newEmptyEntity()
 * @method \App\Model\Entity\Arret newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Arret> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Arret get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Arret findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Arret patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Arret> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Arret|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Arret saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Arret>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Arret>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Arret>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Arret> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Arret>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Arret>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Arret>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Arret> deleteManyOrFail(iterable $entities, array $options = [])
 */
class ArretsTable extends Table
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

        $this->setTable('arrets');
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
            ->date('arret_from')
            ->requirePresence('arret_from', 'create')
            ->notEmptyDate('arret_from');

        $validator
            ->date('arret_to')
            ->requirePresence('arret_to', 'create')
            ->notEmptyDate('arret_to')
            ->greaterThanOrEqual('arret_to', 'arret_from', 'La date de fin doit être après la date de début');

        $validator
            ->integer('arret_diff')
            ->allowEmptyString('arret_diff');

        $validator
            ->date('attestation_date')
            ->allowEmptyDate('attestation_date');

        $validator
            ->date('declaration_date')
            ->allowEmptyDate('declaration_date');

        $validator
            ->integer('rechute')
            ->requirePresence('rechute', 'create')
            ->notEmptyString('rechute');

        $validator
            ->integer('option')
            ->requirePresence('option', 'create')
            ->notEmptyString('option');

        $validator
            ->scalar('code_pathologie')
            ->maxLength('code_pathologie', 10)
            ->requirePresence('code_pathologie', 'create')
            ->notEmptyString('code_pathologie');

        $validator
            ->scalar('adherent_number')
            ->maxLength('adherent_number', 50)
            ->requirePresence('adherent_number', 'create')
            ->notEmptyString('adherent_number');

        $validator
            ->date('birth_date')
            ->requirePresence('birth_date', 'create')
            ->notEmptyDate('birth_date');

        return $validator;
    }

    /**
     * Find arrets by adherent number
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param string $adherentNumber Adherent number
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findByAdherent(SelectQuery $query, string $adherentNumber): SelectQuery
    {
        return $query->where(['adherent_number' => $adherentNumber])
            ->orderBy(['arret_from' => 'ASC']);
    }

    /**
     * Find active arrets (not ended yet)
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where(['arret_to >=' => date('Y-m-d')]);
    }

    /**
     * Convert arrets to calculator format
     *
     * @param array<\App\Model\Entity\Arret> $arrets Arrets entities
     * @return array<int, array<string, mixed>>
     */
    public function toCalculatorFormat(array $arrets): array
    {
        $result = [];
        foreach ($arrets as $arret) {
            $result[] = $arret->toCalculatorFormat();
        }

        return $result;
    }
}
