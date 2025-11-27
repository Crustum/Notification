<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Table
 *
 * @property \Cake\ORM\Association\HasMany $Posts
 * @property \Cake\ORM\Association\HasMany $Notifications
 * @method \TestApp\Model\Entity\User newEmptyEntity()
 * @method \TestApp\Model\Entity\User newEntity(array $data, array $options = [])
 * @method array<\TestApp\Model\Entity\User> newEntities(array $data, array $options = [])
 * @method \TestApp\Model\Entity\User get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \TestApp\Model\Entity\User findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \TestApp\Model\Entity\User patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\TestApp\Model\Entity\User> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \TestApp\Model\Entity\User|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \TestApp\Model\Entity\User saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class UsersTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config Config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('username');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Crustum/Notification.Notifiable');

        $this->hasMany('Posts', [
            'foreignKey' => 'user_id',
            'className' => 'TestApp.Posts',
        ]);
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('username')
            ->maxLength('username', 50)
            ->requirePresence('username', 'create')
            ->notEmptyString('username')
            ->add('username', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->add('email', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('password')
            ->maxLength('password', 255)
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        $validator
            ->scalar('full_name')
            ->maxLength('full_name', 100)
            ->allowEmptyString('full_name');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        return $validator;
    }
}
