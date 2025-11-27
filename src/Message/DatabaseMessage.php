<?php
declare(strict_types=1);

namespace Crustum\Notification\Message;

/**
 * Database Message
 *
 * Represents a notification message that will be stored in the database.
 * This class serves as a data container for database notification payloads.
 *
 * Usage with array:
 * ```
 * return (new DatabaseMessage())
 *     ->data([
 *         'title' => 'New Message',
 *         'message' => 'You have a new message',
 *         'action_url' => '/messages/1',
 *     ]);
 * ```
 *
 * Usage with fluent API:
 * ```
 * return DatabaseMessage::new()
 *     ->title('New Message')
 *     ->message('You have a new message')
 *     ->actionUrl('/messages/1')
 *     ->icon('envelope')
 *     ->type('info');
 * ```
 *
 * @phpstan-consistent-constructor
 */
class DatabaseMessage
{
    use PayloadTrait;

    /**
     * The data that should be stored with the notification
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Create a new database message instance
     *
     * @param array<string, mixed> $data Initial notification data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Create a new database message instance
     *
     * @param array<string, mixed> $data Initial notification data
     * @return static
     * @phpstan-return static
     */
    public static function new(array $data = []): static
    {
        return new static($data);
    }

    /**
     * Set the data for the notification
     *
     * @param array<string, mixed> $data Notification data
     * @return static
     */
    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the notification data
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Convert message to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
