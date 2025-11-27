<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Users Fixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array<string, mixed>>
     */
    public array $records = [
        [
            'id' => 1,
            'username' => 'john',
            'email' => 'john@example.com',
            'password' => 'password123',
            'full_name' => 'John Doe',
            'active' => true,
            'created' => '2025-01-01 10:00:00',
            'modified' => '2025-01-01 10:00:00',
        ],
        [
            'id' => 2,
            'username' => 'jane',
            'email' => 'jane@example.com',
            'password' => 'password456',
            'full_name' => 'Jane Smith',
            'active' => true,
            'created' => '2025-01-02 11:00:00',
            'modified' => '2025-01-02 11:00:00',
        ],
        [
            'id' => 3,
            'username' => 'bob',
            'email' => 'bob@example.com',
            'password' => 'password789',
            'full_name' => 'Bob Johnson',
            'active' => false,
            'created' => '2025-01-03 12:00:00',
            'modified' => '2025-01-03 12:00:00',
        ],
    ];
}
