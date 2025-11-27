<?php
declare(strict_types=1);

namespace Crustum\Notification\Exception;

use Exception;

/**
 * Could Not Send Notification Exception
 *
 * Thrown when a notification channel encounters an error during sending.
 * Provides specific error factory methods for common failure scenarios.
 *
 * Usage:
 * ```
 * throw CouldNotSendNotification::channelNotConfigured('telegram');
 * throw CouldNotSendNotification::serviceRespondedWithError('slack', $response);
 * ```
 */
class CouldNotSendNotification extends Exception
{
    /**
     * The channel that failed
     *
     * @var string
     */
    protected string $channel;

    /**
     * Service response data
     *
     * @var mixed
     */
    protected mixed $response = null;

    /**
     * Create exception for channel not configured
     *
     * @param string $channel Channel name
     * @return static
     */
    public static function channelNotConfigured(string $channel): static
    {
        $exception = new static("Channel '{$channel}' is not configured"); // @phpstan-ignore-line
        $exception->channel = $channel;

        return $exception;
    }

    /**
     * Create exception for service error response
     *
     * @param string $channel Channel name
     * @param mixed $response Service response data
     * @param string|null $message Optional custom message
     * @return static
     */
    public static function serviceRespondedWithError(string $channel, mixed $response, ?string $message = null): static
    {
        $message = $message ?? "Channel '{$channel}' service responded with an error";
        $exception = new static($message); // @phpstan-ignore-line
        $exception->channel = $channel;
        $exception->response = $response;

        return $exception;
    }

    /**
     * Create exception for missing credentials
     *
     * @param string $channel Channel name
     * @param string $credentialName Name of the missing credential
     * @return static
     */
    public static function missingCredentials(string $channel, string $credentialName): static
    {
        $exception = new static( // @phpstan-ignore-line
            "Channel '{$channel}' is missing required credential: {$credentialName}",
        );
        $exception->channel = $channel;

        return $exception;
    }

    /**
     * Create exception for missing routing information
     *
     * @param string $channel Channel name
     * @return static
     */
    public static function missingRoutingInformation(string $channel): static
    {
        $exception = new static( // @phpstan-ignore-line
            "Channel '{$channel}' requires routing information. " .
            "Implement routeNotificationFor{$channel}() method on the entity or use AnonymousNotifiable.",
        );
        $exception->channel = $channel;

        return $exception;
    }

    /**
     * Get the channel that failed
     *
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Get the service response
     *
     * @return mixed
     */
    public function getResponse(): mixed
    {
        return $this->response;
    }
}
