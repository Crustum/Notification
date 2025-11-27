<?php
declare(strict_types=1);

/**
 * Test database schema for Cake plugin tests.
 *
 * This format resembles the existing fixture schema
 * and is converted to SQL via the Schema generation
 * features of the Database package.
 */
return [
    [
        'table' => 'users',
        'columns' => [
            'id' => [
                'type' => 'integer',
                'autoIncrement' => true,
            ],
            'username' => [
                'type' => 'string',
                'length' => 50,
                'null' => false,
            ],
            'email' => [
                'type' => 'string',
                'length' => 100,
                'null' => false,
            ],
            'password' => [
                'type' => 'string',
                'length' => 255,
                'null' => false,
            ],
            'full_name' => [
                'type' => 'string',
                'length' => 100,
                'null' => true,
            ],
            'active' => [
                'type' => 'boolean',
                'default' => true,
                'null' => false,
            ],
            'created' => [
                'type' => 'datetime',
                'null' => true,
            ],
            'modified' => [
                'type' => 'datetime',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
            'users_username_unique' => [
                'type' => 'unique',
                'columns' => [
                    'username',
                ],
            ],
            'users_email_unique' => [
                'type' => 'unique',
                'columns' => [
                    'email',
                ],
            ],
        ],
    ],
    [
        'table' => 'posts',
        'columns' => [
            'id' => [
                'type' => 'integer',
                'autoIncrement' => true,
            ],
            'user_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
                'length' => 255,
                'null' => false,
            ],
            'content' => [
                'type' => 'text',
                'null' => true,
            ],
            'published' => [
                'type' => 'boolean',
                'default' => false,
                'null' => false,
            ],
            'created' => [
                'type' => 'datetime',
                'null' => true,
            ],
            'modified' => [
                'type' => 'datetime',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
            'posts_user_id_fk' => [
                'type' => 'foreign',
                'columns' => [
                    'user_id',
                ],
                'references' => [
                    'users',
                    'id',
                ],
            ],
        ],
    ],
    [
        'table' => 'notifications',
        'columns' => [
            'id' => [
                'type' => 'uuid',
                'null' => false,
            ],
            'model' => [
                'type' => 'string',
                'length' => 255,
                'null' => false,
            ],
            'foreign_key' => [
                'type' => 'string',
                'length' => 255,
                'null' => false,
            ],
            'type' => [
                'type' => 'string',
                'length' => 255,
                'null' => false,
            ],
            'data' => [
                'type' => 'json',
                'null' => false,
            ],
            'read_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
            'created' => [
                'type' => 'datetime',
                'null' => false,
            ],
            'modified' => [
                'type' => 'datetime',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
];
