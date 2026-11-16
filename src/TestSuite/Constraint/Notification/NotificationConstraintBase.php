<?php
declare(strict_types=1);

namespace Crustum\Notification\TestSuite\Constraint\Notification;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Crustum\Notification\TestSuite\TestNotificationSender;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Base class for all notification assertion constraints
 *
 * @internal
 * @uses \Cake\ORM\Locator\LocatorAwareTrait
 */
abstract class NotificationConstraintBase extends Constraint
{
    use LocatorAwareTrait;

    /**
     * Notification index to check
     *
     * @var int|null
     */
    protected ?int $at = null;

    /**
     * Constructor
     *
     * @param int|null $at Optional index of specific notification to check
     */
    public function __construct(?int $at = null)
    {
        $this->at = $at;
    }

    /**
     * Get the notification or notifications to check
     *
     * @return array<array<string, mixed>>
     */
    protected function getNotifications(): array
    {
        $notifications = TestNotificationSender::getNotifications();

        if ($this->at !== null) {
            if (!isset($notifications[$this->at])) {
                return [];
            }

            return [$notifications[$this->at]];
        }

        return $notifications;
    }

    /**
     * Check a captured notification against expected property values
     *
     * Values resolve via `toArray($notifiable)` first, then public property —
     * same precedence as `NotificationDataContains`. Entity values compare by
     * class + source + primary key, everything else with strict equality.
     *
     * @param array<string, mixed> $notificationData Captured payload from `TestNotificationSender`
     * @param array<string, mixed> $expectedData Expected property => value map
     * @return bool True when every expected property matches
     */
    protected function notificationDataMatches(array $notificationData, array $expectedData): bool
    {
        $notification = $notificationData['notification'];
        $notifiable = $notificationData['notifiable'] ?? null;

        foreach ($expectedData as $key => $expected) {
            $found = false;
            $actual = null;

            if (is_object($notification) && method_exists($notification, 'toArray') && $notifiable !== null) {
                $data = $notification->toArray($notifiable);
                if (is_array($data) && array_key_exists($key, $data)) {
                    $found = true;
                    $actual = $data[$key];
                }
            }

            if (!$found && is_object($notification) && property_exists($notification, $key)) {
                $found = true;
                $actual = $notification->{$key};
            }

            if (!$found || !$this->valuesMatch($expected, $actual)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compare an expected value against a captured value
     *
     * @param mixed $expected Expected value
     * @param mixed $actual Captured value
     * @return bool True on match
     */
    protected function valuesMatch(mixed $expected, mixed $actual): bool
    {
        if ($expected instanceof EntityInterface && $actual instanceof EntityInterface) {
            return $expected::class === $actual::class
                && $this->getEntityKey($expected) === $this->getEntityKey($actual);
        }

        return $expected === $actual;
    }

    /**
     * Get a unique key for an entity instance
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity
     * @return string Primary key value, or object hash when the source is unavailable
     */
    protected function getEntityKey(EntityInterface $entity): string
    {
        $source = $entity->getSource();
        if ($source !== '' && $source !== '0') {
            $table = $this->getTableLocator()->get($source);
            $primaryKey = $table->getPrimaryKey();

            if (is_array($primaryKey)) {
                $primaryKey = $primaryKey[0];
            }

            return (string)$entity->get($primaryKey);
        }

        return spl_object_hash($entity);
    }
}
