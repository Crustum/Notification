<?php
declare(strict_types=1);

namespace Crustum\Notification\Channel;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

/**
 * Database Channel
 *
 * Stores notifications in the database for later retrieval.
 * Does NOT send real-time notifications - only persists to database.
 *
 * @uses \Cake\ORM\Locator\LocatorAwareTrait
 */
class DatabaseChannel implements ChannelInterface
{
    use LocatorAwareTrait;

    /**
     * Configuration for the channel
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Channel configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Send the given notification by storing it in the database
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @return \Crustum\Notification\Model\Entity\Notification|false|null The saved notification entity, false on failure, or null if AnonymousNotifiable
     */
    public function send(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): mixed
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return null;
        }

        /** @var \Crustum\Notification\Model\Table\NotificationsTable $notificationsTable */
        $notificationsTable = $this->getTableLocator()->get('Crustum/Notification.Notifications');

        $entity = $notificationsTable->newEntity($this->buildPayload($notifiable, $notification));

        $saved = $notificationsTable->save($entity);

        if ($saved && $saved->id && $saved->id !== $notification->getId()) {
            $notification->setId($saved->id);
        }

        return $saved;
    }

    /**
     * Build the notification payload for database storage
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @return array<string, mixed> The notification payload
     */
    protected function buildPayload(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): array
    {
        $modelName = $notifiable->getSource();
        $table = $this->getTableLocator()->get($modelName);
        $primaryKeyName = $table->getPrimaryKey();

        if (is_array($primaryKeyName)) {
            $primaryKeyName = $primaryKeyName[0];
        }

        $primaryKeyValue = $notifiable->get($primaryKeyName);
        $data = $this->getData($notifiable, $notification);

        $payload = [
            'id' => $notification->getId(),
            'model' => $modelName,
            'foreign_key' => (string)$primaryKeyValue,
            'type' => get_class($notification),
            'data' => $data,
            'read_at' => null,
        ];

        return $payload;
    }

    /**
     * Get the notification data for database storage
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @return array<string, mixed> The notification data
     */
    protected function getData(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): array
    {
        $result = $notification->toDatabase($notifiable);

        if (is_array($result)) {
            return $result;
        }

        return $result->toArray();
    }
}
