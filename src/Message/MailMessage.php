<?php
declare(strict_types=1);

namespace Crustum\Notification\Message;

/**
 * Mail Message
 *
 * Fluent builder for email notification messages.
 */
class MailMessage
{
    public const LEVEL_INFO = 'info';
    public const LEVEL_SUCCESS = 'success';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_WARNING = 'warning';

    /**
     * Email subject
     *
     * @var string|null
     */
    public ?string $subject = null;

    /**
     * Custom view template
     *
     * @var string|null
     */
    public ?string $view = null;

    /**
     * View data
     *
     * @var array<string, mixed>
     */
    public array $viewData = [];

    /**
     * Message level (info, success, error, warning)
     *
     * @var string
     */
    public string $level = self::LEVEL_INFO;

    /**
     * Greeting text
     *
     * @var string|null
     */
    public ?string $greeting = null;

    /**
     * Salutation text
     *
     * @var string|null
     */
    public ?string $salutation = null;

    /**
     * Intro lines (before action)
     *
     * @var array<string>
     */
    public array $introLines = [];

    /**
     * Outro lines (after action)
     *
     * @var array<string>
     */
    public array $outroLines = [];

    /**
     * Action button text
     *
     * @var string|null
     */
    public ?string $actionText = null;

    /**
     * Action button URL
     *
     * @var string|null
     */
    public ?string $actionUrl = null;

    /**
     * From address
     *
     * @var array{address: string, name: string|null}|array{}
     */
    public array $from = [];

    /**
     * Reply-To addresses
     *
     * @var array<array{address: string, name: string|null}>
     */
    public array $replyTo = [];

    /**
     * CC addresses
     *
     * @var array<array{address: string, name: string|null}>
     */
    public array $cc = [];

    /**
     * BCC addresses
     *
     * @var array<array{address: string, name: string|null}>
     */
    public array $bcc = [];

    /**
     * File attachments
     *
     * @var array<array{file: string, options: array<string, mixed>}>
     */
    public array $attachments = [];

    /**
     * Create a new mail message
     *
     * @return static
     */
    public static function create(): static
    {
        return new static(); // @phpstan-ignore-line
    }

    /**
     * Set the subject
     *
     * @param string $subject Email subject
     * @return static
     */
    public function subject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set a custom view template
     *
     * @param string $view Template name
     * @param array<string, mixed> $data View data
     * @return static
     */
    public function view(string $view, array $data = []): static
    {
        $this->view = $view;
        $this->viewData = $data;

        return $this;
    }

    /**
     * Set the message level
     *
     * @param string $level Level (info, success, error, warning)
     * @return static
     */
    public function level(string $level): static
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Set level to success
     *
     * @return static
     */
    public function success(): static
    {
        return $this->level(self::LEVEL_SUCCESS);
    }

    /**
     * Set level to error
     *
     * @return static
     */
    public function error(): static
    {
        return $this->level(self::LEVEL_ERROR);
    }

    /**
     * Set level to warning
     *
     * @return static
     */
    public function warning(): static
    {
        return $this->level(self::LEVEL_WARNING);
    }

    /**
     * Set the greeting
     *
     * @param string $greeting Greeting text
     * @return static
     */
    public function greeting(string $greeting): static
    {
        $this->greeting = $greeting;

        return $this;
    }

    /**
     * Set the salutation
     *
     * @param string $salutation Salutation text
     * @return static
     */
    public function salutation(string $salutation): static
    {
        $this->salutation = $salutation;

        return $this;
    }

    /**
     * Add a line of text
     *
     * @param string $line Text line
     * @return static
     */
    public function line(string $line): static
    {
        if (!$this->actionText) {
            $this->introLines[] = $line;
        } else {
            $this->outroLines[] = $line;
        }

        return $this;
    }

    /**
     * Conditionally add a line of text
     *
     * @param bool $condition Condition to check
     * @param string $line Text line
     * @return static
     */
    public function lineIf(bool $condition, string $line): static
    {
        if ($condition) {
            $this->line($line);
        }

        return $this;
    }

    /**
     * Set action button
     *
     * @param string $text Button text
     * @param string $url Button URL
     * @return static
     */
    public function action(string $text, string $url): static
    {
        $this->actionText = $text;
        $this->actionUrl = $url;

        return $this;
    }

    /**
     * Set from address
     *
     * @param string $address Email address
     * @param string|null $name Sender name
     * @return static
     */
    public function from(string $address, ?string $name = null): static
    {
        $this->from = ['address' => $address, 'name' => $name];

        return $this;
    }

    /**
     * Add reply-to address
     *
     * @param string $address Email address
     * @param string|null $name Name
     * @return static
     */
    public function replyTo(string $address, ?string $name = null): static
    {
        $this->replyTo[] = ['address' => $address, 'name' => $name];

        return $this;
    }

    /**
     * Add CC address
     *
     * @param string $address Email address
     * @param string|null $name Name
     * @return static
     */
    public function cc(string $address, ?string $name = null): static
    {
        $this->cc[] = ['address' => $address, 'name' => $name];

        return $this;
    }

    /**
     * Add BCC address
     *
     * @param string $address Email address
     * @param string|null $name Name
     * @return static
     */
    public function bcc(string $address, ?string $name = null): static
    {
        $this->bcc[] = ['address' => $address, 'name' => $name];

        return $this;
    }

    /**
     * Attach a file
     *
     * @param string $file File path
     * @param array<string, mixed> $options Attachment options
     * @return static
     */
    public function attach(string $file, array $options = []): static
    {
        $this->attachments[] = ['file' => $file, 'options' => $options];

        return $this;
    }

    /**
     * Get array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'subject' => $this->subject,
            'greeting' => $this->greeting,
            'salutation' => $this->salutation,
            'introLines' => $this->introLines,
            'outroLines' => $this->outroLines,
            'actionText' => $this->actionText,
            'actionUrl' => $this->actionUrl,
        ];
    }
}
