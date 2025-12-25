# Notifications Modules

- [Broadcasting Notifications](#broadcasting-notifications)
    - [Prerequisites](#broadcasting-prerequisites)
    - [Formatting Broadcasting Notifications](#formatting-broadcasting-notifications)
    - [Broadcasting Options](#broadcasting-options)
    - [Listening for Notifications](#listening-for-notifications)
- [Slack Notifications](#slack-notifications)
    - [Prerequisites](#slack-prerequisites)
    - [Formatting Slack Notifications](#formatting-slack-notifications)
    - [Slack Attachments](#slack-attachments)
    - [Routing Slack Notifications](#routing-slack-notifications)
- [SMS Notifications (Seven.io)](#sms-notifications)
    - [Prerequisites](#sms-prerequisites)
    - [Formatting SMS Notifications](#formatting-sms-notifications)
    - [Customizing the Sender](#customizing-the-sms-sender)
    - [Routing SMS Notifications](#routing-sms-notifications)
- [Telegram Notifications](#telegram-notifications)
    - [Prerequisites](#telegram-prerequisites)
    - [Formatting Telegram Notifications](#formatting-telegram-notifications)
    - [Telegram Media](#telegram-media)
    - [Routing Telegram Notifications](#routing-telegram-notifications)
- [RocketChat Notifications](#rocketchat-notifications)
    - [Prerequisites](#rocketchat-prerequisites)
    - [Formatting RocketChat Notifications](#formatting-rocketchat-notifications)
    - [Routing RocketChat Notifications](#routing-rocketchat-notifications)
- [Webhook Notifications](#webhook-notifications)
    - [Prerequisites](#webhook-prerequisites)
    - [Formatting Webhook Notifications](#formatting-webhook-notifications)
    - [Routing Webhook Notifications](#routing-webhook-notifications)

<a name="broadcasting-notifications"></a>
## Broadcasting Notifications

<a name="broadcasting-prerequisites"></a>
### Prerequisites

The `broadcast` notification channel sends notifications via WebSocket for real-time delivery. Before you can send notifications via broadcast, you need to install the broadcasting notification plugin:

```shell
composer require crustum/broadcasting-notification
```

Load the plugin in your `Application.php`:

```php
$this->addPlugin('Crustum/BroadcastingNotification');
```

Configure your connection in `config/broadcasting.php`. The plugin supports Pusher-compatible WebSocket servers and Mercure via Server-Sent Events (SSE).

For Pusher, configure your Pusher credentials in the broadcasting configuration.

For Mercure support, install the `crustum/mercure-broadcasting` plugin and configure it in your broadcasting settings. See the Mercure Broadcasting plugin documentation for detailed setup instructions.

<a name="formatting-broadcasting-notifications"></a>
### Formatting Broadcasting Notifications

If a notification supports being broadcast, you should define a `toBroadcast()` method on the notification class. This method will receive a `$notifiable` entity and should return a `BroadcastMessage` instance or array. The returned data will be sent to the WebSocket channel.

**Using Array (Simple):**

```php
/**
 * Get the broadcast representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return array<string, mixed>
 */
public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): array
{
    return [
        'title' => 'New Message',
        'message' => 'You have a new message',
        'type' => 'info',
    ];
}
```

**Using BroadcastMessage (Fluent API):**

```php
use Crustum\BroadcastingNotification\Message\BroadcastMessage;
use Crustum\Notification\Message\Action;

/**
 * Get the broadcast representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return \Crustum\BroadcastingNotification\Message\BroadcastMessage
 */
public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): BroadcastMessage
{
    return BroadcastMessage::new()
        ->title('Order Shipped')
        ->message('Your order #' . $this->order->id . ' has been shipped')
        ->type('success')
        ->icon('truck')
        ->actionUrl(['controller' => 'Orders', 'action' => 'view', $this->order->id])
        ->addAction(
            Action::new('view')
                ->label('View Order')
                ->url(['controller' => 'Orders', 'action' => 'view', $this->order->id])
                ->type('primary')
        );
}
```

<a name="broadcasting-options"></a>
### Broadcasting Options

You may customize the queue connection and queue name used for broadcasting notifications:

```php
public function toBroadcast(EntityInterface|AnonymousNotifiable $notifiable): BroadcastMessage
{
    return BroadcastMessage::new()
        ->title('Important Alert')
        ->message('This is an important system alert')
        ->type('warning')
        ->onQueue('high-priority')
        ->onConnection('redis');
}
```

By default, notifications are broadcast to a private channel named after the notifiable entity (e.g., `App.Model.Entity.User.123`). You can customize the channel by defining a `broadcastOn()` method on your notification:

```php
use Crustum\Broadcasting\Channel\PrivateChannel;

/**
 * Get the channels the event should broadcast on.
 *
 * @return array
 */
public function broadcastOn(): array
{
    return [
        new PrivateChannel('user.' . $this->userId),
    ];
}
```

You can also customize the event name:

```php
/**
 * Get the broadcast event name.
 *
 * @return string|null
 */
public function broadcastAs(): ?string
{
    return 'order.shipped';
}
```

<a name="listening-for-notifications"></a>
### Listening for Notifications

When using the NotificationUI plugin with broadcasting enabled, notifications are automatically received and displayed in real-time. The JavaScript client subscribes to the user's private channel and handles incoming notifications automatically.

For custom implementations, you can listen to broadcast events using Laravel Echo:

```javascript
Echo.private(`App.Model.Entity.User.${userId}`)
    .listen('.App.Notification.OrderShipped', (notification) => {
        console.log('New notification:', notification);
        // Handle the notification in your UI
    });
```

The broadcast event contains all data returned from your `toBroadcast()` method, along with the notification `id` and `type` (class name).

<a name="slack-notifications"></a>
## Slack Notifications

<a name="slack-prerequisites"></a>
### Prerequisites

The `slack` notification channel sends notifications to Slack channels via incoming webhooks. Before you can send notifications via Slack, you need to install the Slack notification plugin:

```shell
composer require crustum/notification-slack
```

Load the plugin in your `Application.php`:

```php
$this->addPlugin('Crustum/SlackNotification');
```

Configure your Slack webhook URL in your application configuration.

<a name="formatting-slack-notifications"></a>
### Formatting Slack Notifications

Slack notifications support two message formats: the modern Block Kit API and the legacy Attachments API. You should define a `toSlack()` method on your notification class that returns either a `BlockKitMessage` or `SlackMessage` instance.

#### Block Kit Messages (Recommended)

Block Kit is Slack's modern framework for building rich, interactive messages. Use `BlockKitMessage` for new implementations:

```php
use Crustum\SlackNotification\BlockKit\BlockKitMessage;

/**
 * Get the Slack representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return \Crustum\SlackNotification\BlockKit\BlockKitMessage
 */
public function toSlack(EntityInterface|AnonymousNotifiable $notifiable): BlockKitMessage
{
    $url = \Cake\Routing\Router::url(
        ['controller' => 'Invoices', 'action' => 'view', $this->invoice->id],
        true
    );

    $message = new BlockKitMessage();

    return $message
        ->text('Invoice payment received')
        ->headerBlock('Invoice Paid')
        ->dividerBlock()
        ->sectionBlock(function ($section) {
            $section->markdown(sprintf(
                '*Invoice #%s* has been paid\n*Amount:* $%s',
                $this->invoice->number,
                number_format($this->invoice->amount, 2)
            ));
        })
        ->actionsBlock(function ($actions) use ($url) {
            $actions->button('View Invoice')
                ->url($url)
                ->primary();
        });
}
```

#### Legacy Attachments API

For simple messages or maintaining backward compatibility, use `SlackMessage`:

```php
use Crustum\SlackNotification\Message\SlackMessage;

/**
 * Get the Slack representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return \Cake\SlackNotification\Message\SlackMessage
 */
public function toSlack(EntityInterface|AnonymousNotifiable $notifiable): SlackMessage
{
    return SlackMessage::new()
        ->text('Invoice Paid: $' . $this->invoice->amount);
}
```

<a name="slack-attachments"></a>
### Slack Attachments

Slack attachments provide a way to add rich formatting and fields to your notifications. You can use attachments to display structured data:

```php
use Crustum\SlackNotification\Message\SlackMessage;

/**
 * Get the Slack representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return \Cake\SlackNotification\Message\SlackMessage
 */
public function toSlack(EntityInterface|AnonymousNotifiable $notifiable): SlackMessage
{
    return SlackMessage::new()
        ->text('Order Shipped')
        ->attachment([
            'color' => 'good',
            'title' => 'Order #' . $this->order->id,
            'text' => 'Your order has been shipped and is on its way',
            'fields' => [
                [
                    'title' => 'Order Number',
                    'value' => $this->order->number,
                    'short' => true,
                ],
                [
                    'title' => 'Tracking Number',
                    'value' => $this->order->tracking_number,
                    'short' => true,
                ],
                [
                    'title' => 'Estimated Delivery',
                    'value' => $this->order->estimated_delivery->format('Y-m-d'),
                    'short' => true,
                ],
            ],
            'footer' => 'Order Management System',
            'ts' => time(),
        ]);
}
```

You can customize the appearance with colors:
- `good` - Green (success)
- `warning` - Yellow (warning)
- `danger` - Red (error)
- Or any hex color code like `#439FE0`

<a name="routing-slack-notifications"></a>
### Routing Slack Notifications

To route Slack notifications to the proper webhook URL or channel, define a `routeNotificationForSlack()` method on your notifiable entity:

```php
<?php
namespace App\Model\Entity;

use Crustum\Notification\Notification;
use Cake\ORM\Entity;

class User extends Entity
{
    /**
     * Route notifications for the Slack channel.
     *
     * @param \Crustum\Notification\Notification $notification
     * @return string
     */
    public function routeNotificationForSlack(Notification $notification): string
    {
        return $this->slack_webhook_url;
    }
}
```

You can also return a channel name to override the default channel configured in the webhook:

```php
public function routeNotificationForSlack(Notification $notification): string|array
{
    return [
        'webhook' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
        'channel' => '#alerts',
    ];
}
```

<a name="sms-notifications"></a>
## SMS Notifications (Seven.io)

<a name="sms-prerequisites"></a>
### Prerequisites

Sending SMS notifications with CakePHP Notification Plugin is powered by Seven.io. Before you can send notifications via Seven, you need to install the `crustum/notification-seven` package and configure your Seven.io API credentials.

<a name="formatting-sms-notifications"></a>
### Formatting SMS Notifications

If a notification supports being sent as an SMS, you should define a `toSeven()` method on the notification class. This method will receive a `$notifiable` entity and should return a `Crustum\Notification\Seven\Message\SevenMessage` instance:

```php
use Crustum\Notification\Seven\Message\SevenMessage;

/**
 * Get the Seven / SMS representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return \Crustum\Notification\Seven\Message\SevenMessage
 */
public function toSeven(EntityInterface|AnonymousNotifiable $notifiable): SevenMessage
{
    return SevenMessage::create('Your SMS message content');
}
```

<a name="customizing-the-sms-sender"></a>
### Customizing the Sender

If you would like to customize the sender name or number, you may use the `from()` method on a `SevenMessage` instance:

```php
use Crustum\Notification\Seven\Message\SevenMessage;

/**
 * Get the Seven / SMS representation of the notification.
 */
public function toSeven(EntityInterface|AnonymousNotifiable $notifiable): SevenMessage
{
    return SevenMessage::create('Your SMS message content')
        ->from('YourApp');
}
```

<a name="routing-sms-notifications"></a>
### Routing SMS Notifications

To route Seven notifications to the proper phone number, define a `routeNotificationForSeven()` method on your notifiable entity:

```php
<?php
namespace App\Model\Entity;

use Crustum\Notification\Notification;
use Cake\ORM\Entity;

class User extends Entity
{
    /**
     * Route notifications for the Seven channel.
     *
     * @param \Crustum\Notification\Notification $notification
     * @return string
     */
    public function routeNotificationForSeven(Notification $notification): string
    {
        return $this->phone_number;
    }
}
```

<a name="telegram-notifications"></a>
## Telegram Notifications

<a name="telegram-prerequisites"></a>
### Prerequisites

Before sending Telegram notifications, you should install the Telegram notification channel and configure your Telegram Bot token.

<a name="formatting-telegram-notifications"></a>
### Formatting Telegram Notifications

If a notification supports being sent to Telegram, you should define a `toTelegram()` method on the notification class. This method will receive a `$notifiable` entity and should return a `Crustum\Notification\Telegram\Message\TelegramMessage` instance:

```php
use Crustum\Notification\Telegram\Enum\ParseMode;
use Crustum\Notification\Telegram\Message\TelegramMessage;

/**
 * Get the Telegram representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return \Crustum\Notification\Telegram\Message\TelegramMessage
 */
public function toTelegram(EntityInterface|AnonymousNotifiable $notifiable): TelegramMessage
{
    return TelegramMessage::create()
        ->content('*Invoice Paid*')
        ->line('Your invoice has been paid!')
        ->parseMode(ParseMode::Markdown);
}
```

<a name="telegram-media"></a>
### Telegram Media

Telegram supports sending various types of media along with messages:

```php
use Crustum\Notification\Telegram\Message\TelegramFile;
use Crustum\Notification\Telegram\Message\TelegramMessage;

public function toTelegram(EntityInterface|AnonymousNotifiable $notifiable): TelegramMessage|TelegramFile
{
    // Send a photo
    return TelegramFile::create('/path/to/image.jpg', TelegramFile::TYPE_PHOTO)
        ->caption('Invoice receipt');

    // Send a document
    return TelegramFile::create('/path/to/document.pdf', TelegramFile::TYPE_DOCUMENT)
        ->caption('Invoice PDF');
}
```

<a name="routing-telegram-notifications"></a>
### Routing Telegram Notifications

To route Telegram notifications to the proper chat, define a `routeNotificationForTelegram()` method on your notifiable entity:

```php
<?php
namespace App\Model\Entity;

use Crustum\Notification\Notification;
use Cake\ORM\Entity;

class User extends Entity
{
    /**
     * Route notifications for the Telegram channel.
     *
     * @param \Crustum\Notification\Notification $notification
     * @return string
     */
    public function routeNotificationForTelegram(Notification $notification): string
    {
        return $this->telegram_chat_id;
    }
}
```

<a name="rocketchat-notifications"></a>
## RocketChat Notifications

<a name="rocketchat-prerequisites"></a>
### Prerequisites

Before sending RocketChat notifications, you should install the RocketChat notification channel and configure your webhook URL.

<a name="formatting-rocketchat-notifications"></a>
### Formatting RocketChat Notifications

If a notification supports being sent to RocketChat, you should define a `toRocketchat()` method on the notification class. This method will receive a `$notifiable` entity and should return a `Crustum\Notification\RocketChat\Message\RocketChatMessage` instance:

```php
use Crustum\Notification\RocketChat\Message\RocketChatAttachment;
use Crustum\Notification\RocketChat\Message\RocketChatMessage;

/**
 * Get the RocketChat representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return \Crustum\Notification\RocketChat\Message\RocketChatMessage
 */
public function toRocketchat(EntityInterface|AnonymousNotifiable $notifiable): RocketChatMessage
{
    return RocketChatMessage::create()
        ->text('Invoice Paid')
        ->emoji(':money_with_wings:')
        ->attachment(function (RocketChatAttachment $attachment) {
            $attachment->title('Invoice #1234')
                ->text('Your invoice has been paid')
                ->color('good')
                ->field('Amount', '$99.00', true)
                ->field('Date', date('Y-m-d'), true);
        });
}
```

<a name="routing-rocketchat-notifications"></a>
### Routing RocketChat Notifications

To route RocketChat notifications to the proper webhook, define a `routeNotificationForRocketchat()` method on your notifiable entity:

```php
<?php
namespace App\Model\Entity;

use Crustum\Notification\Notification;
use Cake\ORM\Entity;

class User extends Entity
{
    /**
     * Route notifications for the RocketChat channel.
     *
     * @param \Crustum\Notification\Notification $notification
     * @return string
     */
    public function routeNotificationForRocketchat(Notification $notification): string
    {
        return $this->rocketchat_webhook_url;
    }
}
```

<a name="webhook-notifications"></a>
## Webhook Notifications

<a name="webhook-prerequisites"></a>
### Prerequisites

The webhook notification channel allows you to send notifications to arbitrary HTTP endpoints.

<a name="formatting-webhook-notifications"></a>
### Formatting Webhook Notifications

If a notification supports being sent to a webhook, you should define a `toWebhook()` method on the notification class. This method will receive a `$notifiable` entity and should return a `Crustum\Notification\Webhook\Message\WebhookMessage` instance:

```php
use Crustum\Notification\Webhook\Message\WebhookMessage;

/**
 * Get the webhook representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return \Crustum\Notification\Webhook\Message\WebhookMessage
 */
public function toWebhook(EntityInterface|AnonymousNotifiable $notifiable): WebhookMessage
{
    return WebhookMessage::create([
        'event' => 'invoice.paid',
        'invoice_id' => $this->invoice->id,
        'amount' => $this->invoice->amount,
    ]);
}
```

You can also customize headers and other options:

```php
public function toWebhook(EntityInterface|AnonymousNotifiable $notifiable): WebhookMessage
{
    return WebhookMessage::create([
        'event' => 'invoice.paid',
        'data' => $this->invoice->toArray(),
    ])
    ->header('X-Webhook-Secret', 'your-secret')
    ->verify(false); // Disable SSL verification
}
```

<a name="routing-webhook-notifications"></a>
### Routing Webhook Notifications

To route webhook notifications to the proper URL, define a `routeNotificationForWebhook()` method on your notifiable entity:

```php
<?php
namespace App\Model\Entity;

use Crustum\Notification\Notification;
use Cake\ORM\Entity;

class User extends Entity
{
    /**
     * Route notifications for the Webhook channel.
     *
     * @param \Crustum\Notification\Notification $notification
     * @return string
     */
    public function routeNotificationForWebhook(Notification $notification): string
    {
        return $this->webhook_url;
    }
}
```
