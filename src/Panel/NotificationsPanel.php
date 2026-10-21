<?php
declare(strict_types=1);

namespace Crustum\Notification\Panel;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Crustum\DebugBar\Service\VarExporter;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Message\DatabaseMessage;
use Crustum\Notification\Message\MailMessage;
use Crustum\Notification\Notification;
use Throwable;

/**
 * Notifications debug panel.
 *
 * Duck-typed third-party panel for the DebugBar toolbar (loaded through its
 * legacy adapter, no dependency on toolbar classes): subscribes to the
 * `Model.Notification.*` events the sender dispatches and records one
 * delivery attempt per notifiable/channel pair. Attempts correlate by
 * notification object identity — the sender reuses the same per-channel
 * clone from `sending` through its terminal event — while durations are
 * measured here since the sender captures none. Attempts group by
 * notifiable plus notification class; the inbox drains on `data()` for
 * long-lived workers.
 *
 * Cake-honest subset of the upstream inspector: only `sent`, `failed`,
 * `skipped` and `pending` (sending seen, no terminal event by shutdown)
 * statuses are reported. Queue states are never invented — queued sends
 * emit no events in the web request until a worker runs.
 */
class NotificationsPanel implements EventListenerInterface
{
    /**
     * Plugin alias locating the panel element.
     *
     * @var string
     */
    public string $plugin = 'Crustum/Notification';

    /**
     * Captured delivery attempts for the current request.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $inbox = [];

    /**
     * Records one delivery attempt under its correlation key.
     *
     * Test support mirroring the toolbar core panels.
     *
     * @param string $key Correlation key.
     * @param array<string, mixed> $attempt Attempt shape.
     * @return void
     */
    public static function record(string $key, array $attempt): void
    {
        static::$inbox[$key] = $attempt;
    }

    /**
     * Resets captured attempts (long-lived workers and tests).
     *
     * @return void
     */
    public static function clear(): void
    {
        static::$inbox = [];
    }

    /**
     * Reads captured attempts without draining.
     *
     * Test support for panel assertions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function recorded(): array
    {
        return static::$inbox;
    }

    /**
     * Panel title shown in the inspector navigation.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Notifications';
    }

    /**
     * Element rendering the panel, resolved inside this plugin.
     *
     * @return string
     */
    public function elementName(): string
    {
        return 'Crustum/Notification.notifications_panel';
    }

    /**
     * Groups captured attempts for the inspector.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $groups = static::group(static::$inbox);
        static::$inbox = [];

        $attention = 0;
        $toFacets = [];
        $classFacets = [];
        foreach ($groups as $group) {
            if (($group['status'] ?? 'sent') !== 'sent') {
                $attention++;
            }

            foreach ((array)($group['filter_facets'] ?? []) as $token => $label) {
                if (str_starts_with((string)$token, 'to:')) {
                    $toFacets[(string)$token] = $label;
                } elseif (str_starts_with((string)$token, 'class:')) {
                    $classFacets[(string)$token] = $label;
                }
            }
        }

        return [
            'groups' => $groups,
            'summary' => ['count' => count($groups), 'attention' => $attention],
            'facets' => ['to' => $toFacets, 'class' => $classFacets],
        ];
    }

    /**
     * Toolbar badge for the panel.
     *
     * @return string
     */
    public function summary(): string
    {
        $count = count(static::$inbox);

        return $count === 0 ? '' : "{$count} notifications";
    }

    /**
     * No setup work beyond event subscription.
     *
     * @return void
     */
    public function initialize(): void
    {
    }

    /**
     * Subscribes the panel to notification lifecycle events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'Model.Notification.sending' => 'captureSending',
            'Model.Notification.sent' => 'captureSent',
            'Model.Notification.failed' => 'captureFailed',
            'Model.Notification.skipped' => 'captureSkipped',
        ];
    }

    /**
     * Starts one delivery attempt timer.
     *
     * @param \Cake\Event\EventInterface $event Sending event.
     * @return void
     */
    public function captureSending(EventInterface $event): void
    {
        $notifiable = $event->getData('notifiable');
        $notification = $event->getData('notification');
        $channel = (string)$event->getData('channel');
        if (!$notifiable instanceof EntityInterface && !$notifiable instanceof AnonymousNotifiable) {
            return;
        }

        if (!$notification instanceof Notification) {
            return;
        }

        static::$inbox[static::key($notification, $channel)] = [
            'notifiable' => static::notifiable($notifiable, $channel),
            'notification' => $notification::class,
            'notification_id' => $notification->getId(),
            'locale' => $notification->getLocale(),
            'queue' => $notification->getQueue(),
            'connection' => $notification->getConnection(),
            'delay' => $notification->getDelay(),
            'notification_data' => static::payload($notifiable, $notification),
            'channel' => $channel,
            'status' => 'pending',
            'started' => microtime(true),
            'duration_ms' => 0.0,
            'response' => null,
            'failure' => null,
            'callsite' => static::callsite(),
        ];
    }

    /**
     * Completes one delivery attempt as sent.
     *
     * @param \Cake\Event\EventInterface $event Sent event.
     * @return void
     */
    public function captureSent(EventInterface $event): void
    {
        $attempt = &static::attempt($event);
        if ($attempt === null) {
            return;
        }

        $attempt['status'] = 'sent';
        $attempt['duration_ms'] = (microtime(true) - (float)$attempt['started']) * 1000;
        $attempt['response'] = static::plain($event->getData('response'));
        $attempt['response_summary'] = static::responseSummary($event->getData('response'));
    }

    /**
     * Completes one delivery attempt as failed.
     *
     * @param \Cake\Event\EventInterface $event Failed event.
     * @return void
     */
    public function captureFailed(EventInterface $event): void
    {
        $attempt = &static::attempt($event);
        if ($attempt === null) {
            return;
        }

        $error = $event->getData('exception');
        $attempt['status'] = 'failed';
        $attempt['duration_ms'] = (microtime(true) - (float)$attempt['started']) * 1000;
        $attempt['failure'] = $error instanceof Throwable ? [
            'class' => $error::class,
            'message' => mb_strcut($error->getMessage(), 0, 2000),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
        ] : ['class' => '', 'message' => 'Channel threw.', 'file' => '', 'line' => 0];
    }

    /**
     * Completes one delivery attempt as skipped.
     *
     * @param \Cake\Event\EventInterface $event Skipped event.
     * @return void
     */
    public function captureSkipped(EventInterface $event): void
    {
        $attempt = &static::attempt($event);
        if ($attempt === null) {
            return;
        }

        $attempt['status'] = 'skipped';
        $attempt['duration_ms'] = (microtime(true) - (float)$attempt['started']) * 1000;
    }

    /**
     * Finds the attempt matching a terminal event, starting it when the
     * sending event was never observed.
     *
     * @param \Cake\Event\EventInterface $event Terminal event.
     * @return array<string, mixed>|null
     */
    protected static function &attempt(EventInterface $event): ?array
    {
        $notification = $event->getData('notification');
        $channel = (string)$event->getData('channel');
        if (!$notification instanceof Notification) {
            $missing = null;

            return $missing;
        }

        $key = static::key($notification, $channel);
        static::$inbox[$key] ??= [
            'notifiable' => static::notifiable($event->getData('notifiable'), $channel),
            'notification' => $notification::class,
            'notification_id' => $notification->getId(),
            'locale' => $notification->getLocale(),
            'queue' => $notification->getQueue(),
            'connection' => $notification->getConnection(),
            'delay' => $notification->getDelay(),
            'notification_data' => [],
            'channel' => $channel,
            'status' => 'pending',
            'started' => microtime(true),
            'duration_ms' => 0.0,
            'response' => null,
            'failure' => null,
            'callsite' => null,
        ];

        return static::$inbox[$key];
    }

    /**
     * Correlates lifecycle events of one channel delivery.
     *
     * The sender reuses the same per-channel notification clone from
     * `sending` through its terminal event, so object identity is stable.
     *
     * @param \Crustum\Notification\Notification $notification Notification instance.
     * @param string $channel Channel name.
     * @return string
     */
    protected static function key(Notification $notification, string $channel): string
    {
        return spl_object_id($notification) . ':' . $channel;
    }

    /**
     * Shapes notifiable identity plus the resolved destination.
     *
     * @param mixed $notifiable Notifiable entity or anonymous route.
     * @param string $channel Channel name.
     * @return array<string, mixed>
     */
    protected static function notifiable(mixed $notifiable, string $channel): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            $route = $notifiable->routeNotificationFor($channel);
            $label = is_scalar($route) && trim((string)$route) !== ''
                ? trim((string)$route)
                : 'Anonymous route';

            return [
                'type' => 'Anonymous',
                'id' => null,
                'name' => null,
                'label' => $label,
                'destination' => $label,
                'routes' => is_scalar($route) ? [$channel => $route] : [],
            ];
        }

        if ($notifiable instanceof EntityInterface) {
            $type = $notifiable->getSource();
            $short = $type !== '' ? static::short($type) : 'Entity';
            $id = null;
            foreach (['id', 'uuid'] as $key) {
                if ($notifiable->has($key) && is_scalar($notifiable->get($key))) {
                    $id = (string)$notifiable->get($key);

                    break;
                }
            }

            $name = null;
            foreach (['name', 'username', 'email', 'title'] as $field) {
                if ($notifiable->has($field) && is_scalar($notifiable->get($field))) {
                    $candidate = trim((string)$notifiable->get($field));
                    if ($candidate !== '') {
                        $name = $candidate;

                        break;
                    }
                }
            }

            $destination = null;
            $method = 'routeNotificationFor' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $channel)));
            if (method_exists($notifiable, $method)) {
                try {
                    $resolved = $notifiable->{$method}();
                    if (is_scalar($resolved) && trim((string)$resolved) !== '') {
                        $destination = trim((string)$resolved);
                    }
                } catch (Throwable) {
                    $destination = null;
                }
            }

            $label = $name ?? $short . ($id !== null && $id !== '' ? ' #' . $id : '');

            return [
                'type' => $short,
                'id' => $id,
                'name' => $name,
                'label' => $label,
                'destination' => $destination ?? $label,
                'routes' => [],
            ];
        }

        return [
            'type' => 'Notifiable',
            'id' => null,
            'name' => null,
            'label' => 'Notifiable',
            'destination' => 'No destination resolved',
            'routes' => [],
        ];
    }

    /**
     * Reads the notification payload without leaking failures.
     *
     * Falls back from `toArray()` through `toDatabase()` to `toMail()`
     * because channel-specific notifications often skip the generic
     * array shape while carrying a full channel payload.
     *
     * @param mixed $notifiable Notifiable entity or anonymous route.
     * @param \Crustum\Notification\Notification $notification Notification instance.
     * @return array<string, mixed>
     */
    protected static function payload(mixed $notifiable, Notification $notification): array
    {
        if (!$notifiable instanceof EntityInterface && !$notifiable instanceof AnonymousNotifiable) {
            return [];
        }

        try {
            $data = $notification->toArray($notifiable);
            if ($data !== []) {
                $plain = static::plain($data);

                return is_array($plain) ? $plain : [];
            }
        } catch (Throwable) {
            // Fall through to channel payloads.
        }

        try {
            $message = $notification->toDatabase($notifiable);
            if ($message instanceof DatabaseMessage) {
                $plain = static::plain($message->getData());

                return is_array($plain) ? $plain : [];
            }

            if ($message !== []) {
                $plain = static::plain($message);

                return is_array($plain) ? $plain : [];
            }
        } catch (Throwable) {
            // Fall through to the mail payload.
        }

        try {
            $mail = $notification->toMail($notifiable);
            if ($mail instanceof MailMessage) {
                $plain = static::plain($mail->toArray());

                return is_array($plain) ? $plain : [];
            }

            if (is_string($mail) && trim($mail) !== '') {
                return ['body' => $mail];
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }

    /**
     * Normalizes arbitrary values into JSON-safe plain data.
     *
     * Scalars pass through; everything else exports the Variables way —
     * `VarExporter` keeps inner objects (entities, dates, throwables)
     * expandable instead of collapsing them to bare class names.
     *
     * @param mixed $value Value to normalize.
     * @param int $depth Remaining recursion depth.
     * @return mixed
     */
    protected static function plain(mixed $value, int $depth = 4): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        return VarExporter::export($value, max(1, $depth));
    }

    /**
     * Captures the userland callsite plus a capped frame list.
     *
     * Skips sender internals and framework paths so the first frame
     * points at the application code that triggered the send; vendor
     * frames stay in the list, flagged as framework ones.
     *
     * @return array<string, mixed>|null
     */
    protected static function callsite(): ?array
    {
        $frames = [];
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $class = (string)($frame['class'] ?? '');
            if (str_starts_with($class, 'Crustum\\Notification\\')) {
                continue;
            }

            $file = $frame['file'] ?? '';
            if ($file === '') {
                continue;
            }

            $vendor = str_contains($file, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR);
            $frames[] = [
                'file' => $file,
                'line' => $frame['line'] ?? 0,
                'function' => $class !== ''
                    ? $class . ($frame['type'] ?? '') . $frame['function']
                    : $frame['function'],
                'vendor' => $vendor,
            ];

            if (count($frames) >= 24) {
                break;
            }
        }

        if ($frames === []) {
            return null;
        }

        $first = $frames[0];
        foreach ($frames as $frame) {
            if ($frame['vendor'] === false) {
                $first = $frame;

                break;
            }
        }

        return ['file' => $first['file'], 'line' => $first['line'], 'frames' => $frames];
    }

    /**
     * Short class name without namespace.
     *
     * @param string $class Fully qualified class name.
     * @return string
     */
    protected static function short(string $class): string
    {
        $class = ltrim($class, '\\');
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }

    /**
     * URL-safe filter token fragment.
     *
     * @param string $value Label text.
     * @return string
     */
    protected static function slug(string $value): string
    {
        $slug = mb_strtolower(trim($value));

        return trim((string)preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
    }

    /**
     * Groups attempts by notifiable plus notification class.
     *
     * @param array<string, array<string, mixed>> $attempts Captured attempts.
     * @return array<int, array<string, mixed>>
     */
    protected static function group(array $attempts): array
    {
        $buckets = [];
        foreach ($attempts as $attempt) {
            $recipient = (array)($attempt['notifiable'] ?? []);
            $key = ($recipient['type'] ?? '?') . '|' . ($recipient['id'] ?? $recipient['label'] ?? '?')
                . '|' . ($attempt['notification'] ?? '?');
            $buckets[$key][] = $attempt;
        }

        $groups = [];
        foreach (array_values($buckets) as $index => $bucket) {
            $first = (array)$bucket[0];
            $deliveries = [];
            $sent = 0;
            $failed = 0;
            $skipped = 0;
            $pending = false;
            $duration = 0.0;
            $channels = [];
            $destinations = [];
            $failures = [];
            foreach ($bucket as $position => $attempt) {
                $attempt = (array)$attempt;
                $status = (string)($attempt['status'] ?? 'pending');
                if ($status === 'sent') {
                    $sent++;
                } elseif ($status === 'failed') {
                    $failed++;
                } elseif ($status === 'skipped') {
                    $skipped++;
                } else {
                    $pending = $pending || $status === 'pending';
                }

                $duration += (float)($attempt['duration_ms'] ?? 0);
                $channel = (string)($attempt['channel'] ?? 'unknown');
                $channels[] = $channel;
                $destination = (string)(($attempt['notifiable'] ?? [])['destination'] ?? '');
                if ($destination !== '') {
                    $destinations[] = $destination;
                }

                $failure = (array)($attempt['failure'] ?? []);
                if (($failure['message'] ?? '') !== '') {
                    $failures[] = (string)$failure['message'];
                }

                $deliveries[] = static::delivery($attempt, $position);
            }

            $total = count($bucket);
            if ($sent === $total) {
                $status = 'sent';
            } elseif ($failed === $total) {
                $status = 'failed';
            } elseif ($skipped === $total) {
                $status = 'skipped';
            } elseif ($pending) {
                $status = 'pending';
            } else {
                $status = 'partial';
            }

            $statusLabel = match ($status) {
                'sent' => 'Sent',
                'failed' => 'Failed',
                'pending' => 'Pending',
                'skipped' => 'Skipped',
                default => 'Needs attention',
            };
            $channels = array_values(array_unique($channels));
            $recipient = (array)$first['notifiable'];
            $notification = (string)$first['notification'];
            $recipientLabel = (string)($recipient['label'] ?? '');
            $filterTokens = [$status === 'sent' ? 'sent' : 'attention'];
            $filterFacets = [];
            $toToken = 'to:' . static::slug($recipientLabel);
            if ($toToken !== 'to:') {
                $filterTokens[] = $toToken;
                $filterFacets[$toToken] = $recipientLabel;
            }

            $classToken = 'class:' . static::slug(static::short($notification));
            if ($classToken !== 'class:') {
                $filterTokens[] = $classToken;
                $filterFacets[$classToken] = static::short($notification);
            }

            $groups[] = [
                'group_id' => 'notification-' . ($index + 1),
                'notification' => $notification,
                'label' => static::short($notification),
                'status' => $status,
                'status_label' => $statusLabel,
                'sent_count' => $sent,
                'failed_count' => $failed,
                'duration_ms' => $duration,
                'duration_label' => static::formatDuration($duration),
                'delivery_count' => $total,
                'deliveries' => $deliveries,
                'channels' => $channels,
                'channels_label' => implode(', ', array_map(static::channelLabel(...), $channels)),
                'locale' => $first['locale'] ?? null,
                'queue' => $first['queue'] ?? null,
                'connection' => $first['connection'] ?? null,
                'delay' => $first['delay'] ?? null,
                'notification_id' => $first['notification_id'] ?? null,
                'notification_data' => (array)($first['notification_data'] ?? []),
                'recipient' => $recipient,
                'routes' => (array)($recipient['routes'] ?? []),
                'callsite' => $first['callsite'] ?? null,
                'filter' => implode(' ', $filterTokens),
                'filter_facets' => $filterFacets,
                'search' => mb_strtolower(implode(' ', array_filter([
                    $notification,
                    (string)($recipient['type'] ?? ''),
                    (string)($recipient['label'] ?? ''),
                    implode(' ', $channels),
                    implode(' ', $destinations),
                    $statusLabel,
                    implode(' ', $failures),
                ]))),
            ];
        }

        return $groups;
    }

    /**
     * Summarizes a channel response for list rows.
     *
     * @param mixed $response Channel response.
     * @return string|null
     */
    protected static function responseSummary(mixed $response): ?string
    {
        if (is_scalar($response) && trim((string)$response) !== '') {
            return trim((string)$response);
        }

        if (is_array($response) && is_scalar($response['message_id'] ?? null)) {
            return 'Message ' . $response['message_id'];
        }

        if ($response instanceof EntityInterface) {
            $recordId = null;
            foreach (['id', 'uuid'] as $key) {
                if ($response->has($key) && is_scalar($response->get($key))) {
                    $recordId = (string)$response->get($key);

                    break;
                }
            }

            return 'Database record' . ($recordId !== null && $recordId !== '' ? ' #' . $recordId : '');
        }

        if (is_object($response)) {
            return $response::class;
        }

        return null;
    }

    /**
     * Shapes one delivery attempt for the inspector.
     *
     * @param array<string, mixed> $attempt Captured attempt.
     * @param int $position Position inside its group.
     * @return array<string, mixed>
     */
    protected static function delivery(array $attempt, int $position): array
    {
        $channel = (string)($attempt['channel'] ?? 'unknown');
        $status = (string)($attempt['status'] ?? 'pending');
        $statusLabel = match ($status) {
            'sent' => 'Sent to channel',
            'failed' => 'Failed',
            'skipped' => 'Skipped',
            default => 'Pending',
        };
        $duration = (float)($attempt['duration_ms'] ?? 0);
        $failure = (array)($attempt['failure'] ?? []);
        $response = $attempt['response'] ?? null;
        $responseSummary = $attempt['response_summary'] ?? null;

        $destination = (string)(($attempt['notifiable'] ?? [])['destination'] ?? '');

        return [
            'execution' => $position + 1,
            'channel' => $channel,
            'channel_label' => static::channelLabel($channel),
            'status' => $status,
            'status_label' => $statusLabel,
            'duration_ms' => $duration,
            'duration_label' => static::formatDuration($duration),
            'destination_label' => $destination !== '' ? $destination : 'No destination resolved',
            'destination_resolved' => $destination !== '',
            'response' => static::plain($response),
            'response_summary' => $responseSummary,
            'failure_message' => (string)($failure['message'] ?? ''),
            'exception_class' => (string)($failure['class'] ?? ''),
            'exception_location' => isset($failure['file']) && $failure['file'] !== '' ? [
                'file' => (string)$failure['file'],
                'line' => (int)($failure['line'] ?? 0),
            ] : null,
        ];
    }

    /**
     * Human channel label.
     *
     * @param string $channel Channel name.
     * @return string
     */
    protected static function channelLabel(string $channel): string
    {
        return str_replace(['Sms', 'Mms', 'Api', 'Url'], ['SMS', 'MMS', 'API', 'URL'], ucfirst($channel));
    }

    /**
     * Formats milliseconds for list rows.
     *
     * @param float $milliseconds Duration.
     * @return string
     */
    protected static function formatDuration(float $milliseconds): string
    {
        if ($milliseconds >= 1000) {
            return number_format($milliseconds / 1000, 2) . ' s';
        }

        if ($milliseconds >= 1) {
            return number_format($milliseconds, $milliseconds >= 100 ? 0 : 1) . ' ms';
        }

        return number_format($milliseconds * 1000, 0) . ' µs';
    }
}
