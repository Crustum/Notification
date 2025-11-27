<?php
declare(strict_types=1);

namespace TestApp\Model\Table;

use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use TestApp\Notification\PostPublished;

/**
 * Posts Table
 *
 * @property \Cake\ORM\Association\BelongsTo $Users
 * @method \TestApp\Model\Entity\Post newEmptyEntity()
 * @method \TestApp\Model\Entity\Post newEntity(array $data, array $options = [])
 * @method array<\TestApp\Model\Entity\Post> newEntities(array $data, array $options = [])
 * @method \TestApp\Model\Entity\Post get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \TestApp\Model\Entity\Post findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \TestApp\Model\Entity\Post patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\TestApp\Model\Entity\Post> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \TestApp\Model\Entity\Post|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \TestApp\Model\Entity\Post saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class PostsTable extends Table
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

        $this->setTable('posts');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
            'className' => 'TestApp.Users',
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
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('content')
            ->allowEmptyString('content');

        $validator
            ->boolean('published')
            ->notEmptyString('published');

        return $validator;
    }

    /**
     * After save callback - send notification when post is published
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param \ArrayObject $options Options
     * @return void
     */
    public function afterSave(EventInterface $event, $entity, $options): void
    {
        if ($entity->published && $entity->isDirty('published')) {
            $user = $this->Users->get($entity->user_id);

            $this->Users->notify($user, new PostPublished($entity->id, $entity->title));
        }
    }
}
