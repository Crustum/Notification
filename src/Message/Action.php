<?php
declare(strict_types=1);

namespace Crustum\Notification\Message;

use Cake\Routing\Router;

/**
 * Notification Action
 *
 * Fluent builder for notification action buttons.
 *
 * @phpstan-consistent-constructor
 */
class Action
{
    /**
     * Action name/identifier
     *
     * @var string
     */
    protected string $name;

    /**
     * Action label (button text)
     *
     * @var string|null
     */
    protected ?string $label = null;

    /**
     * Action URL
     *
     * @var array<string, mixed>|string|null
     */
    protected string|array|null $url = null;

    /**
     * Action type/color
     *
     * @var string|null
     */
    protected ?string $type = null;

    /**
     * Icon identifier
     *
     * @var string|null
     */
    protected ?string $icon = null;

    /**
     * Constructor
     *
     * @param string $name Action identifier
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Create a new action instance
     *
     * @param string $name Action identifier
     * @return static
     * @phpstan-return static
     */
    public static function new(string $name): static
    {
        return new static($name);
    }

    /**
     * Set the action label
     *
     * @param string $label Button text
     * @return static
     */
    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the action URL
     *
     * @param array<string, mixed>|string $url Target URL or routing array
     * @return static
     */
    public function url(string|array $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the action type/color
     *
     * @param string $type Action type (primary, success, danger, etc.)
     * @return static
     */
    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the action icon
     *
     * @param string $icon Icon identifier
     * @return static
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Convert action to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
        ];

        if ($this->label !== null) {
            $data['label'] = $this->label;
        }

        if ($this->url !== null) {
            $data['url'] = is_array($this->url) ? Router::url($this->url, true) : $this->url;
        }

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }

        if ($this->icon !== null) {
            $data['icon'] = $this->icon;
        }

        return $data;
    }
}
