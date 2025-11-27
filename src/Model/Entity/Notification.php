<?php
declare(strict_types=1);

namespace Crustum\Notification\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * Notification Entity
 *
 * Represents a single notification record in the database.
 * Provides methods for marking notifications as read/unread and checking read status.
 *
 * @property string $id Unique notification identifier (UUID)
 * @property string $model The model name that owns this notification (e.g., "Users", "Posts")
 * @property string $foreign_key The foreign key value of the notifiable entity
 * @property string $type The fully qualified notification class name
 * @property array<string, mixed> $data The notification payload data stored as JSON
 * @property \Cake\I18n\DateTime|null $read_at Timestamp when notification was marked as read
 * @property \Cake\I18n\DateTime $created Creation timestamp
 * @property \Cake\I18n\DateTime $modified Last modification timestamp
 * @property bool $is_read Virtual property indicating if notification has been read
 * @property bool $is_unread Virtual property indicating if notification is unread
 */
class Notification extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity()
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'model' => true,
        'foreign_key' => true,
        'type' => true,
        'data' => true,
        'read_at' => true,
        'created' => false,
        'modified' => false,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity
     *
     * @var array<string>
     */
    protected array $_hidden = [];

    /**
     * Virtual fields that should be exposed
     *
     * @var array<string>
     */
    protected array $_virtual = [
        'is_read',
        'is_unread',
    ];

    /**
     * Get the is_read virtual property value
     *
     * @return bool True if notification has been read, false otherwise
     */
    protected function _getIsRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Get the is_unread virtual property value
     *
     * @return bool True if notification is unread, false otherwise
     */
    protected function _getIsUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Mark this notification as read by setting the read_at timestamp
     *
     * @return bool True if notification was marked as read, false if already read
     */
    public function markAsRead(): bool
    {
        if ($this->read_at === null) {
            $this->read_at = new DateTime();

            return true;
        }

        return false;
    }

    /**
     * Mark this notification as unread by clearing the read_at timestamp
     *
     * @return bool True if notification was marked as unread, false if already unread
     */
    public function markAsUnread(): bool
    {
        if ($this->read_at !== null) {
            $this->read_at = null;

            return true;
        }

        return false;
    }
}
