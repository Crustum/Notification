<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite\Constraint\Notification;

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Crustum\Notification\AnonymousNotifiable;

/**
 * NotificationSentTo
 *
 * Asserts that a notification was sent to a specific notifiable entity
 *
 * @internal
 */
class NotificationSentTo extends NotificationConstraintBase
{
    /**
     * Checks if notification was sent to the notifiable
     *
     * @param mixed $other Array with 'notifiable' and 'class' keys
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        $notifiable = $other['notifiable'];
        $notificationClass = $other['class'];

        $notifiableClass = get_class($notifiable);
        $notifiableId = static::getNotifiableKey($notifiable);

        $notifications = $this->getNotifications();

        foreach ($notifications as $notification) {
            if (
                $notification['notifiable_class'] === $notifiableClass &&
                $notification['notifiable_id'] === $notifiableId &&
                $notification['notification_class'] === $notificationClass
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get unique key for notifiable
     *
     * @param object $notifiable The notifiable
     * @return string
     */
    protected static function getNotifiableKey(object $notifiable): string
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return 'anonymous_' . spl_object_hash($notifiable);
        }

        if ($notifiable instanceof EntityInterface) {
            $source = $notifiable->getSource();
            if ($source) {
                $table = TableRegistry::getTableLocator()->get($source);
                $primaryKey = $table->getPrimaryKey();

                if (is_array($primaryKey)) {
                    $primaryKey = $primaryKey[0];
                }

                return (string)$notifiable->get($primaryKey);
            }
        }

        return spl_object_hash($notifiable);
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at !== null) {
            return sprintf('notification #%d was sent to notifiable', $this->at);
        }

        return 'notification was sent to notifiable';
    }
}
