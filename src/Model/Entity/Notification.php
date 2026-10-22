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
 * Uses CakePHP 6 hooked entity properties: concrete typed properties with
 * `public protected(set)` visibility. Reads via `$entity->field` hit the
 * property directly; writes still flow through `set()` for dirty tracking,
 * so entity internals must use `$this->set()` instead of direct assignment.
 */
class Notification extends Entity
{
    public protected(set) string $id;

    public protected(set) string $model;

    public protected(set) string $foreign_key;

    public protected(set) string $type;

    /**
     * @var array<string, mixed>
     */
    public protected(set) array $data = [];

    public protected(set) ?DateTime $read_at = null;

    public protected(set) ?DateTime $created = null;

    public protected(set) ?DateTime $modified = null;

    public bool $is_read {
        get => $this->read_at !== null;
    }

    public bool $is_unread {
        get => $this->read_at === null;
    }

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity()
     *
     * @var array<string, bool>
     */
    protected array $patchable = [
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
    protected array $hidden = [];

    /**
     * Virtual fields that should be exposed
     *
     * @var array<string>
     */
    protected array $virtual = [
        'is_read',
        'is_unread',
    ];

    /**
     * Mark this notification as read by setting the read_at timestamp
     *
     * @return bool True if notification was marked as read, false if already read
     */
    public function markAsRead(): bool
    {
        if ($this->read_at === null) {
            $this->set('read_at', new DateTime());

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
            $this->set('read_at', null);

            return true;
        }

        return false;
    }
}
