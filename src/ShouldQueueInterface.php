<?php
declare(strict_types=1);

namespace Crustum\Notification;

/**
 * Should Queue Interface
 *
 * Marker interface indicating that a notification should be queued for async processing.
 * Notifications implementing this interface will be dispatched to the queue system
 * instead of being sent immediately.
 *
 * Usage:
 * ```
 * class WeeklyReportNotification extends Notification implements ShouldQueueInterface
 * {
 *     public function via(EntityInterface|AnonymousNotifiable $notifiable): array
 *     {
 *         return ['database', 'mail'];
 *     }
 * }
 * ```
 */
interface ShouldQueueInterface
{
}
