<?php
declare(strict_types=1);

namespace Crustum\Notification\Job;

use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Crustum\Notification\Notification;
use Crustum\Notification\NotificationManager;
use Exception;
use Interop\Queue\Processor;
use Throwable;

/**
 * Send Queued Notification Job
 *
 * Handles sending queued notifications from the queue system.
 * Uses JSON serialization to store notification data in queue.
 *
 * @uses \Cake\ORM\Locator\LocatorAwareTrait
 */
class SendQueuedNotificationJob implements JobInterface
{
    use LocatorAwareTrait;

    /**
     * Execute the queued notification job
     *
     * @param \Cake\Queue\Job\Message $message Queue message
     * @return string Job execution result (ACK, REJECT, or REQUEUE)
     */
    public function execute(Message $message): string
    {
        $notifiableModel = $message->getArgument('notifiableModel');
        $notifiableForeignKey = $message->getArgument('notifiableForeignKey');
        $serializedNotification = $message->getArgument('notification');
        $channels = $message->getArgument('channels');

        if (empty($notifiableModel) || empty($notifiableForeignKey) || empty($serializedNotification)) {
            Log::error('Notification job received invalid arguments');

            return Processor::REJECT;
        }

        if (!is_string($serializedNotification)) {
            Log::error('Invalid notification data: expected serialized string');

            return Processor::REJECT;
        }

        try {
            $notification = $this->reconstructNotification($serializedNotification);

            try {
                $notifiable = $this->loadNotifiable($notifiableModel, $notifiableForeignKey);
            } catch (RecordNotFoundException $exception) {
                if ($notification->shouldDeleteWhenMissingModels()) {
                    return Processor::ACK;
                }

                throw $exception;
            }

            NotificationManager::sendNow($notifiable, $notification, $channels);

            return Processor::ACK;
        } catch (Throwable $throwable) {
            Log::error('Notification job failed: ' . $throwable->getMessage(), [
                'exception' => $throwable,
                'trace' => $throwable->getTraceAsString(),
                'notification' => $serializedNotification,
                'channels' => $channels ?? [],
            ]);

            return Processor::REQUEUE;
        }
    }

    /**
     * Load the notifiable entity from the database
     *
     * @param string $model Model name (e.g., "Users", "Posts")
     * @param string $foreignKey Primary key value
     * @return \Cake\Datasource\EntityInterface The loaded entity
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When the entity does not exist
     */
    protected function loadNotifiable(string $model, string $foreignKey): EntityInterface
    {
        $table = $this->getTableLocator()->get($model);

        return $table->get($foreignKey);
    }

    /**
     * Reconstruct notification from serialized string
     *
     * Uses PHP's unserialize() which automatically calls __unserialize() magic method.
     *
     * @param string $serialized Serialized notification string
     * @return \Crustum\Notification\Notification The notification instance
     * @throws \Exception When unserialization fails or result is not a Notification
     */
    protected function reconstructNotification(string $serialized): Notification
    {
        $notification = unserialize($serialized);

        if (!$notification instanceof Notification) {
            throw new Exception('Unserialized data is not a Notification instance');
        }

        return $notification;
    }
}
