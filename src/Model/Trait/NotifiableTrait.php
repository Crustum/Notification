<?php
declare(strict_types=1);

namespace Crustum\Notification\Model\Trait;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Query\SelectQuery;
use Crustum\Notification\Notification;
use Crustum\Notification\NotificationManager;

/**
 * Notifiable Trait
 *
 * Provides notification methods for tables. Use this trait in your table class
 * along with the NotifiableBehavior for association creation.
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
 */
trait NotifiableTrait
{
    /**
     * Send a notification to the given entity
     *
     * The notification will be sent through all channels defined in the notification's via() method.
     * If the notification implements ShouldQueueInterface, it will be queued for async processing.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to notify
     * @param \Crustum\Notification\Notification $notification The notification instance
     * @return void
     */
    public function notify(EntityInterface $entity, Notification $notification): void
    {
        NotificationManager::send($entity, $notification);
    }

    /**
     * Send a notification immediately, bypassing the queue
     *
     * The notification will be sent immediately through the specified channels,
     * even if it implements ShouldQueueInterface.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to notify
     * @param \Crustum\Notification\Notification $notification The notification instance
     * @param array<string>|null $channels Optional array of channel names to send through
     * @return void
     */
    public function notifyNow(EntityInterface $entity, Notification $notification, ?array $channels = null): void
    {
        NotificationManager::sendNow($entity, $notification, $channels);
    }

    /**
     * Get the routing information for a given notification channel
     *
     * This method checks for a specific routing method on the entity first,
     * then falls back to default routing for known channels.
     *
     * @param object $entity The entity to get routing info for
     * @param string $channel The channel name (e.g., 'database', 'mail', 'slack')
     * @param \Crustum\Notification\Notification|null $notification The notification instance
     * @return mixed Routing information for the channel
     */
    public function routeNotificationFor(object $entity, string $channel, ?Notification $notification = null): mixed
    {
        $method = 'routeNotificationFor' . ucfirst($channel);

        if (method_exists($entity, $method)) {
            return $entity->{$method}($notification);
        }

        if ($channel === 'database') {
            return $this->getAssociation('Notifications');
        }

        return null;
    }

    /**
     * Mark a notification as read for this entity
     *
     * @param object $entity The entity
     * @param string $notificationId The notification ID
     * @return bool True if marked as read
     */
    public function markNotificationAsRead(object $entity, string $notificationId): bool
    {
        /** @var \Crustum\Notification\Model\Table\NotificationsTable $notificationsTable */
        $notificationsTable = $this->getAssociation('Notifications')->getTarget();

        return $notificationsTable->markAsRead($notificationId);
    }

    /**
     * Mark all notifications as read for this entity
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity
     * @return int Number of notifications marked as read
     */
    public function markAllNotificationsAsRead(EntityInterface $entity): int
    {
        /** @var \Crustum\Notification\Model\Table\NotificationsTable $notificationsTable */
        $notificationsTable = $this->getAssociation('Notifications')->getTarget();
        $primaryKey = $this->getPrimaryKey();
        if (is_array($primaryKey)) {
            $primaryKey = $primaryKey[0];
        }

        return $notificationsTable->markAllAsRead(
            $this->getAlias(),
            (string)$entity->get($primaryKey),
        );
    }

    /**
     * Get unread notifications query for this entity
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity
     * @return \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification>
     * @phpstan-return \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification>
     */
    public function unreadNotifications(EntityInterface $entity): SelectQuery
    {
        /** @var \Crustum\Notification\Model\Table\NotificationsTable $notificationsTable */
        $notificationsTable = $this->getAssociation('Notifications')->getTarget();
        $primaryKey = $this->getPrimaryKey();
        if (is_array($primaryKey)) {
            $primaryKey = $primaryKey[0];
        }

        /** @phpstan-var \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification> */
        return $notificationsTable
            ->find('forModel', model: $this->getAlias(), foreign_key: (string)$entity->get($primaryKey))
            ->find('unread');
    }

    /**
     * Get read notifications query for this entity
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity
     * @return \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification>
     * @phpstan-return \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification>
     */
    public function readNotifications(EntityInterface $entity): SelectQuery
    {
        /** @var \Crustum\Notification\Model\Table\NotificationsTable $notificationsTable */
        $notificationsTable = $this->getAssociation('Notifications')->getTarget();
        $primaryKey = $this->getPrimaryKey();
        if (is_array($primaryKey)) {
            $primaryKey = $primaryKey[0];
        }

        /** @phpstan-var \Cake\ORM\Query\SelectQuery<\Crustum\Notification\Model\Entity\Notification> */
        return $notificationsTable
            ->find('forModel', model: $this->getAlias(), foreign_key: (string)$entity->get($primaryKey))
            ->find('read');
    }
}
