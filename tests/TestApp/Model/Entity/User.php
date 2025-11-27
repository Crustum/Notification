<?php
declare(strict_types=1);

namespace TestApp\Model\Entity;

use Cake\ORM\Entity;
use Crustum\Notification\Notification;

/**
 * User Entity
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $full_name
 * @property bool $active
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \TestApp\Model\Entity\Post[] $posts
 * @property \Crustum\Notification\Model\Entity\Notification[] $notifications
 */
class User extends Entity
{
    /**
     * Fields that can be mass assigned
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'username' => true,
        'email' => true,
        'password' => true,
        'full_name' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'posts' => true,
        'notifications' => true,
    ];

    /**
     * Route notifications for the mail channel
     *
     * @param \Crustum\Notification\Notification $notification Notification instance
     * @return string
     */
    public function routeNotificationForMail(Notification $notification): string
    {
        return $this->email;
    }
}
