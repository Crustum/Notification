<?php
declare(strict_types=1);

namespace Crustum\Notification\Model\Table;

use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Notifications Model
 *
 * Manages the notifications table which stores all notification records.
 * Uses model + foreign_key pattern for flexible association with any entity type.
 *
 * Custom Finders:
 * - read: Find only read notifications
 * - unread: Find only unread notifications
 * - forModel: Find notifications for specific model and/or foreign key
 *
 * @method \Crustum\Notification\Model\Entity\Notification newEmptyEntity()
 * @method \Crustum\Notification\Model\Entity\Notification newEntity(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\Crustum\Notification\Model\Entity\Notification> newEntities(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method \Crustum\Notification\Model\Entity\Notification get(mixed $primaryKey, array<string>|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Crustum\Notification\Model\Entity\Notification findOrCreate($search, ?callable $callback = null, array<string, mixed> $options = [])
 * @method \Crustum\Notification\Model\Entity\Notification patchEntity(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method array<\Crustum\Notification\Model\Entity\Notification> patchEntities(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method \Crustum\Notification\Model\Entity\Notification|false save(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method \Crustum\Notification\Model\Entity\Notification saveOrFail(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method iterable<\Crustum\Notification\Model\Entity\Notification>|\Cake\Datasource\ResultSetInterface<\Crustum\Notification\Model\Entity\Notification>|false saveMany(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @method iterable<\Crustum\Notification\Model\Entity\Notification>|\Cake\Datasource\ResultSetInterface<\Crustum\Notification\Model\Entity\Notification> saveManyOrFail(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @method iterable<\Crustum\Notification\Model\Entity\Notification>|\Cake\Datasource\ResultSetInterface<\Crustum\Notification\Model\Entity\Notification>|false deleteMany(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @method iterable<\Crustum\Notification\Model\Entity\Notification>|\Cake\Datasource\ResultSetInterface<\Crustum\Notification\Model\Entity\Notification> deleteManyOrFail(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class NotificationsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config Configuration for the Table
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('notifications');
        $this->setDisplayField('type');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->getSchema()->setColumnType('data', 'json');
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
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('model')
            ->maxLength('model', 255)
            ->requirePresence('model', 'create')
            ->notEmptyString('model');

        $validator
            ->scalar('foreign_key')
            ->maxLength('foreign_key', 255)
            ->requirePresence('foreign_key', 'create')
            ->notEmptyString('foreign_key');

        $validator
            ->scalar('type')
            ->maxLength('type', 255)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->requirePresence('data', 'create')
            ->notEmptyString('data');

        $validator
            ->dateTime('read_at')
            ->allowEmptyDateTime('read_at');

        return $validator;
    }

    /**
     * Custom finder to retrieve only read notifications
     *
     * @param \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification> $query The query object
     * @return \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification>
     */
    public function findRead(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('read_at') . ' IS NOT' => null]);
    }

    /**
     * Custom finder to retrieve only unread notifications
     *
     * @param \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification> $query The query object
     * @return \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification>
     */
    public function findUnread(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('read_at') . ' IS' => null]);
    }

    /**
     * Custom finder to retrieve notifications for a specific model and/or foreign key
     *
     * @param \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification> $query The query object
     * @param string|null $model Filter by model name (e.g., "Users", "Posts")
     * @param string|null $foreign_key Filter by specific foreign key value
     * @return \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification>
     */
    public function findForModel(SelectQuery $query, ?string $model = null, ?string $foreign_key = null): SelectQuery
    {
        if ($model !== null) {
            $query->where([$this->aliasField('model') => $model]);
        }

        if ($foreign_key !== null) {
            $query->where([$this->aliasField('foreign_key') => $foreign_key]);
        }

        return $query;
    }

    /**
     * Mark a single notification as read
     *
     * @param string $id Notification ID
     * @return bool True if marked as read, false otherwise
     */
    public function markAsRead(string $id): bool
    {
        $notification = $this->get($id);
        if ($notification->read_at === null) {
            $notification->read_at = new DateTime();

            return (bool)$this->save($notification);
        }

        return false;
    }

    /**
     * Mark multiple notifications as read
     *
     * @param array<string> $ids Array of notification IDs
     * @return int Number of notifications marked as read
     */
    public function markManyAsRead(array $ids): int
    {
        return $this->updateAll(
            ['read_at' => new DateTime()],
            [
                'id IN' => $ids,
                'read_at IS' => null,
            ],
        );
    }

    /**
     * Mark all notifications as read for a specific entity
     *
     * @param string $model Model name (e.g., "Users")
     * @param string $foreignKey Entity ID
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(string $model, string $foreignKey): int
    {
        return $this->updateAll(
            ['read_at' => new DateTime()],
            [
                'model' => $model,
                'foreign_key' => $foreignKey,
                'read_at IS' => null,
            ],
        );
    }

    /**
     * Mark a single notification as unread
     *
     * @param string $id Notification ID
     * @return bool True if marked as unread, false otherwise
     */
    public function markAsUnread(string $id): bool
    {
        $notification = $this->get($id);
        if ($notification->read_at !== null) {
            $notification->read_at = null;

            return (bool)$this->save($notification);
        }

        return false;
    }
}
