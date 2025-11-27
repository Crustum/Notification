<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * CreateNotificationsTable Migration
 *
 * Creates the notifications table for storing notification records.
 */
class CreateNotificationsTable extends BaseMigration
{
    /**
     * Creates the notifications table
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('notifications', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('foreign_key', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('type', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('data', 'json', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('read_at', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addIndex(['model', 'foreign_key', 'created'], [
                'name' => 'idx_notifiable',
            ])
            ->addIndex(['model', 'created'], [
                'name' => 'idx_model_created',
            ])
            ->addIndex(['type'], [
                'name' => 'idx_type',
            ])
            ->addIndex(['read_at'], [
                'name' => 'idx_read_at',
            ])
            ->create();
    }
}

