<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Posts Fixture
 */
class PostsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array<string, mixed>>
     */
    public array $records = [
        [
            'id' => 1,
            'user_id' => 1,
            'title' => 'First Post',
            'content' => 'This is the content of the first post',
            'published' => false,
            'created' => '2025-01-01 10:00:00',
            'modified' => '2025-01-01 10:00:00',
        ],
        [
            'id' => 2,
            'user_id' => 1,
            'title' => 'Second Post',
            'content' => 'This is the content of the second post',
            'published' => true,
            'created' => '2025-01-02 11:00:00',
            'modified' => '2025-01-02 11:00:00',
        ],
        [
            'id' => 3,
            'user_id' => 2,
            'title' => 'Jane\'s Post',
            'content' => 'Post by Jane',
            'published' => false,
            'created' => '2025-01-03 12:00:00',
            'modified' => '2025-01-03 12:00:00',
        ],
    ];
}
