<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Notifications Fixture
 *
 * Provides test data for notification-related tests
 */
class NotificationsFixture extends TestFixture
{
    /**
     * Import table schema from the notifications table
     *
     * @var array<string, mixed>
     */
    public array $import = ['table' => 'notifications'];

    /**
     * Test records
     *
     * @var array<array<string, mixed>>
     */
    public array $records = [
        [
            'id' => 'notification-read-1',
            'model' => 'Users',
            'foreign_key' => 'user-uuid-1',
            'type' => 'App\\Notification\\WelcomeNotification',
            'data' => '{"message": "Welcome to our app!"}',
            'read_at' => '2025-10-12 10:00:00',
            'created' => '2025-10-12 09:00:00',
            'modified' => '2025-10-12 10:00:00',
        ],
        [
            'id' => 'notification-unread-1',
            'model' => 'Users',
            'foreign_key' => 'user-uuid-1',
            'type' => 'App\\Notification\\MessageNotification',
            'data' => '{"message": "You have a new message"}',
            'read_at' => null,
            'created' => '2025-10-13 08:00:00',
            'modified' => '2025-10-13 08:00:00',
        ],
        [
            'id' => 'notification-unread-2',
            'model' => 'Posts',
            'foreign_key' => 'post-id-1',
            'type' => 'App\\Notification\\CommentNotification',
            'data' => '{"message": "New comment on your post"}',
            'read_at' => null,
            'created' => '2025-10-13 09:30:00',
            'modified' => '2025-10-13 09:30:00',
        ],
    ];
}
