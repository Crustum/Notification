<?php
declare(strict_types=1);

namespace Crustum\Notification\Model\Behavior;

use Cake\ORM\Behavior;

/**
 * Notifiable Behavior
 *
 * Creates hasMany association to Notifications table. For methods, use NotifiableTrait.
 *
 * Usage:
 * ```
 * use Crustum\Notification\Model\Trait\NotifiableTrait;
 *
 * class UsersTable extends Table
 * {
 *     use NotifiableTrait;
 *
 *     public function initialize(array $config): void
 *     {
 *         $this->addBehavior('Crustum/Notification.Notifiable');
 *     }
 * }
 * ```
 *
 * @property \Cake\ORM\Table $table
 */
class NotifiableBehavior extends Behavior
{
    /**
     * Default configuration
     *
     * @var array<string, mixed>
     */
    protected array $defaultConfig = [];

    /**
     * Initialize hook
     *
     * Automatically creates hasMany association to Notifications table with proper conditions
     * based on the model name.
     *
     * @param array<string, mixed> $config Configuration options
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $modelName = $this->table->getAlias();

        $this->table->hasMany('Notifications', [
            'className' => 'Crustum/Notification.Notifications',
            'foreignKey' => 'foreign_key',
            'conditions' => ['Notifications.model' => $modelName],
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }
}
