<?php
declare(strict_types=1);

namespace Crustum\Notification\View\Helper;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Helper;
use Crustum\Notification\Model\Entity\Notification;
use Throwable;

/**
 * Notifications Helper
 *
 * Helper for rendering and formatting notifications in views
 *
 * @uses \Cake\ORM\Locator\LocatorAwareTrait
 */
class NotificationsHelper extends Helper
{
    use LocatorAwareTrait;

    /**
     * Helpers
     *
     * @var array<string>
     */
    protected array $helpers = ['Html', 'Url', 'Form'];

    /**
     * Default configuration
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [];

    /**
     * Get notification title
     *
     * Tries in order:
     * 1. Call getTitle() on notification class instance
     * 2. Use 'title' from notification data
     * 3. Convert class name to title case
     *
     * @param \Crustum\Notification\Model\Entity\Notification $notification Notification entity
     * @return string
     */
    public function getNotificationTitle(Notification $notification): string
    {
        if (class_exists($notification->type)) {
            try {
                $instance = new $notification->type();
                if (method_exists($instance, 'getTitle')) {
                    return $instance->getTitle();
                }
            } catch (Throwable) {
            }
        }

        if (isset($notification->data['title'])) {
            return $notification->data['title'];
        }

        $parts = explode('\\', $notification->type);
        $className = end($parts);
        $title = preg_replace('/(?<!^)[A-Z]/', ' $0', $className);

        return __($title ?: $className);
    }

    /**
     * Get notification message
     *
     * Tries in order:
     * 1. Use 'message' from notification data
     * 2. Use 'title' from notification data
     * 3. Generic message
     *
     * @param \Crustum\Notification\Model\Entity\Notification $notification Notification entity
     * @return string
     */
    public function getNotificationMessage(Notification $notification): string
    {
        if (isset($notification->data['message'])) {
            return $notification->data['message'];
        }

        return $notification->data['title'] ?? __('You have a new notification');
    }

    /**
     * Get notification types for filtering
     *
     * Returns distinct notification types from database
     *
     * @return array<string, string>
     */
    public function getNotificationTypes(): array
    {
        $notificationsTable = $this->getTableLocator()->get('Crustum/Notification.Notifications');

        $types = $notificationsTable->find()
            ->select(['type'])
            ->distinct(['type'])
            ->orderByAsc('type')
            ->all()
            ->extract('type')
            ->toArray();

        $typeMap = [];
        foreach ($types as $type) {
            $parts = explode('\\', (string)$type);
            $className = end($parts);
            $label = preg_replace('/(?<!^)[A-Z]/', ' $0', $className);
            $typeMap[$type] = __($label ?: $className);
        }

        return $typeMap;
    }

    /**
     * Get notification icon class
     *
     * Tries in order:
     * 1. Call getIcon() on notification class instance
     * 2. Use 'icon' from notification data
     * 3. Default to 'bell'
     *
     * @param \Crustum\Notification\Model\Entity\Notification $notification Notification entity
     * @return string
     */
    public function getNotificationIcon(Notification $notification): string
    {
        if (class_exists($notification->type)) {
            try {
                $instance = new $notification->type();
                if (method_exists($instance, 'getIcon')) {
                    return $instance->getIcon();
                }
            } catch (Throwable) {
            }
        }

        return $notification->data['icon'] ?? 'bell';
    }

    /**
     * Format notification data as HTML
     *
     * @param \Crustum\Notification\Model\Entity\Notification $notification Notification entity
     * @return string
     */
    public function formatNotificationData(Notification $notification): string
    {
        if (empty($notification->data)) {
            return '';
        }

        $output = '<dl class="notification-data">';
        foreach ($notification->data as $key => $value) {
            if ($key === 'message') {
                continue;
            }
            if ($key === 'title') {
                continue;
            }
            $label = __(ucfirst(str_replace('_', ' ', $key)));
            $output .= sprintf(
                '<dt>%s</dt><dd>%s</dd>',
                h($label),
                h($value),
            );
        }

        return $output . '</dl>';
    }
}
