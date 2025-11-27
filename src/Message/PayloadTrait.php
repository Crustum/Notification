<?php
declare(strict_types=1);

namespace Crustum\Notification\Message;

/**
 * Payload Trait
 *
 * Provides fluent builder methods for notification payload data.
 * Used by DatabaseMessage and BroadcastMessage.
 */
trait PayloadTrait
{
    /**
     * Set the notification title
     *
     * @param string $title Title text
     * @return static
     */
    public function title(string $title): static
    {
        $this->data['title'] = $title;

        return $this;
    }

    /**
     * Set the notification message
     *
     * @param string $message Message text
     * @return static
     */
    public function message(string $message): static
    {
        $this->data['message'] = $message;

        return $this;
    }

    /**
     * Set the notification type
     *
     * @param string $type Type (success, info, warning, danger)
     * @return static
     */
    public function type(string $type): static
    {
        $this->data['type'] = $type;

        return $this;
    }

    /**
     * Set the action URL
     *
     * @param string $url Action URL
     * @return static
     */
    public function actionUrl(string $url): static
    {
        $this->data['action_url'] = $url;

        return $this;
    }

    /**
     * Set the icon
     *
     * @param string $icon Icon identifier
     * @return static
     */
    public function icon(string $icon): static
    {
        $this->data['icon'] = $icon;

        return $this;
    }

    /**
     * Set the icon CSS class
     *
     * @param string $iconClass CSS class (e.g., 'fa fa-check')
     * @return static
     */
    public function iconClass(string $iconClass): static
    {
        $this->data['icon_class'] = $iconClass;

        return $this;
    }

    /**
     * Set notification actions
     *
     * @param array<\Crustum\Notification\Message\Action|array<string, mixed>> $actions Action buttons
     * @return static
     */
    public function actions(array $actions): static
    {
        $this->data['actions'] = array_map(
            fn($action) => $action instanceof Action ? $action->toArray() : $action,
            $actions,
        );

        return $this;
    }

    /**
     * Add a single action
     *
     * @param \Crustum\Notification\Message\Action|array<string, mixed> $action Action button
     * @return static
     */
    public function addAction(Action|array $action): static
    {
        if (!isset($this->data['actions'])) {
            $this->data['actions'] = [];
        }

        $this->data['actions'][] = $action instanceof Action ? $action->toArray() : $action;

        return $this;
    }
}
