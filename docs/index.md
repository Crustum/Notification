# Notifications

- [Introduction](#introduction)
- [Quickstart](#quickstart)
- [Generating Notifications](#generating-notifications)
- [Sending Notifications](#sending-notifications)
    - [Using the Notifiable Behavior](#using-the-notifiable-behavior)
    - [Using the NotificationManager](#using-the-notificationmanager)
    - [Specifying Delivery Channels](#specifying-delivery-channels)
    - [Queueing Notifications](#queueing-notifications)
    - [On-Demand Notifications](#on-demand-notifications)
- [Mail Notifications](#mail-notifications)
    - [Formatting Mail Messages](#formatting-mail-messages)
    - [Customizing the Sender](#customizing-the-sender)
    - [Customizing the Recipient](#customizing-the-recipient)
    - [Customizing the Subject](#customizing-the-subject)
    - [Customizing the Templates](#customizing-the-templates)
    - [Attachments](#mail-attachments)
    - [Using Custom Templates](#using-custom-templates)
- [Database Notifications](#database-notifications)
    - [Prerequisites](#database-prerequisites)
    - [Formatting Database Notifications](#formatting-database-notifications)
    - [Accessing the Notifications](#accessing-the-notifications)
    - [Marking Notifications as Read](#marking-notifications-as-read)
- [Notification UI](#notification-ui)
    - [Installation](#notification-ui-installation)
    - [Usage](#notification-ui-usage)
    - [Configuration Options](#notification-ui-configuration)
    - [Real-Time Broadcasting](#notification-ui-broadcasting)
- [Localizing Notifications](#localizing-notifications)
- [Testing](#testing)
- [Notification Events](#notification-events)
- [Custom Channels](#custom-channels)

<a name="introduction"></a>
## Introduction

The CakePHP Notification plugin provides support for sending notifications across a variety of delivery channels, including email, SMS (via Seven.io), Telegram, RocketChat, Slack, and Webhooks. Notifications may also be stored in a database so they may be displayed in your web interface. Additionally, broadcasting notifications are handled by a separate plugin.

Typically, notifications should be short, informational messages that notify users of something that occurred in your application. For example, if you are writing a billing application, you might send an "Invoice Paid" notification to your users via the email and SMS channels.


<a name="quickstart"></a>
## Quickstart

### Installing the Plugin

Install via Composer:

```bash
composer require crustum/notification
```

> [!NOTE]
> This plugin should be registered in your `config/plugins.php` file.

```bash
bin/cake plugin load Crustum/Notification
```

> [!TIP]
> **After the plugin registers itself**, it's recommended to install the configuration with the manifest system:

```bash
bin/cake manifest install --plugin Crustum/Notification
```

The Notification plugin will create the `config/notification.php` configuration file where you may register your application's notification channels. Additionally, it will copy the migrations to the application's migrations directory and append the loading of the `config/notification.php` file to the `config/bootstrap.php` file.

<a name="generating-notifications"></a>
## Generating Notifications

In CakePHP, each notification is represented by a single class that is typically stored in the `src/Notification` directory. You can generate a notification class using the bake command:

```shell
bin/cake bake notification InvoicePaid
```

This command will place a fresh notification class in your `src/Notification` directory. Each notification class contains a `via()` method and a variable number of message building methods, such as `toMail()` or `toDatabase()`, that convert the notification to a message tailored for that particular channel.

### Specifying Channels

You can specify which channels to include when generating the notification:

```shell
bin/cake bake notification InvoicePaid --channels database,mail,slack
```

<a name="sending-notifications"></a>
## Sending Notifications

<a name="using-the-notifiable-behavior"></a>
### Using the Notifiable Behavior

Notifications may be sent in two ways: using the `notify()` method provided by the `NotifiableTrait` or using the `NotificationManager`. To enable notifications on a table, you must use both the `NotifiableTrait` and add the `NotifiableBehavior`:

```php
<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Crustum\Notification\Model\Trait\NotifiableTrait;

class UsersTable extends Table
{
    use NotifiableTrait;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->addBehavior('Crustum/Notification.Notifiable');
    }
}
```

The `notify()` method that is provided by the trait is called on the table and receives the entity and notification instance:

```php
use App\Notification\InvoicePaid;

$usersTable = $this->getTableLocator()->get('Users');
$user = $usersTable->get(1);

$usersTable->notify($user, new InvoicePaid($invoice));
```

> [!NOTE]
> You may add the `NotifiableTrait` and `NotifiableBehavior` to any of your tables. You are not limited to only including it on your `Users` table.

<a name="using-the-notificationmanager"></a>
### Using the NotificationManager

Alternatively, you may send notifications via the `NotificationManager`. This approach is useful when you need to send a notification to multiple notifiable entities such as a collection of users. To send notifications using the manager, pass all of the notifiable entities and the notification instance to the `send()` method:

```php
use Crustum\Notification\NotificationManager;
use App\Notification\InvoicePaid;

NotificationManager::send($users, new InvoicePaid($invoice));
```

You can also send notifications immediately using the `sendNow()` method. This method will send the notification immediately even if the notification implements the `ShouldQueueInterface`:

```php
NotificationManager::sendNow($developers, new DeploymentCompleted($deployment));
```

<a name="specifying-delivery-channels"></a>
### Specifying Delivery Channels

Every notification class has a `via()` method that determines on which channels the notification will be delivered. Notifications may be sent on the `mail`, `database`, `broadcast`, `seven`, `telegram`, `slack`, `rocketchat`, and `webhook` channels.

The `via()` method receives a `$notifiable` instance, which will be an instance of the entity to which the notification is being sent. You may use `$notifiable` to determine which channels the notification should be delivered on:

```php
use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;

/**
 * Get the notification's delivery channels.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return array<string>
 */
public function via(EntityInterface|AnonymousNotifiable $notifiable): array
{
    return $notifiable->prefers_sms ? ['seven'] : ['mail', 'database'];
}
```

<a name="queueing-notifications"></a>
### Queueing Notifications

> **Warning:** Before queueing notifications, you should configure your queue and start a worker.

Sending notifications can take time, especially if the channel needs to make an external API call to deliver the notification. To speed up your application's response time, let your notification be queued by implementing the `ShouldQueueInterface` interface to your class. The interface is already imported for all notifications, so you may immediately implement it:

```php
<?php
namespace App\Notification;

use Crustum\Notification\Notification;
use Crustum\Notification\ShouldQueueInterface;

class InvoicePaid extends Notification implements ShouldQueueInterface
{
    // ...
}
```

Once the `ShouldQueueInterface` has been implemented on your notification, you may send the notification like normal. The system will detect the interface on the class and automatically queue the delivery of the notification:

```php
$usersTable = $this->getTableLocator()->get('Users');
$user = $usersTable->get(1);

$usersTable->notify($user, new InvoicePaid($invoice));
```

When queueing notifications, a queued job will be created for each recipient and channel combination. For example, six jobs will be dispatched to the queue if your notification has three recipients and two channels.

#### Delaying Notifications

If you would like to delay the delivery of the notification, you may use the `delay()` method on your notification instantiation:

```php
$delay = 600; // 10 minutes in seconds

$usersTable = $this->getTableLocator()->get('Users');
$user = $usersTable->get(1);

$usersTable->notify($user, (new InvoicePaid($invoice))->delay($delay));
```

#### Customizing the Notification Queue Connection

By default, queued notifications will be queued using your application's default queue connection. If you would like to specify a different connection that should be used for a particular notification, you may set the `$connection` property on your notification class:

```php
<?php
namespace App\Notification;

use Crustum\Notification\Notification;
use Crustum\Notification\ShouldQueueInterface;

class InvoicePaid extends Notification implements ShouldQueueInterface
{
    protected ?string $connection = 'redis';

    // ...
}
```

#### Customizing Notification Channel Queues

If you would like to specify a specific queue that should be used for the notification, you may set the `$queue` property on your notification class:

```php
<?php
namespace App\Notification;

use Crustum\Notification\Notification;
use Crustum\Notification\ShouldQueueInterface;

class InvoicePaid extends Notification implements ShouldQueueInterface
{
    protected ?string $queue = 'notifications';

    // ...
}
```

#### Determining if a Queued Notification Should Be Sent

After a queued notification has been dispatched for the queue for background processing, it will typically be accepted by a queue worker and sent to its intended recipient.

However, if you would like to make the final determination on whether the queued notification should be sent after it is being processed by a queue worker, you may define a `shouldSend()` method on the notification class. If this method returns `false`, the notification will not be sent:

```php
/**
 * Determine if the notification should be sent.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @param string $channel
 * @return bool
 */
public function shouldSend(EntityInterface|AnonymousNotifiable $notifiable, string $channel): bool
{
    return $this->invoice->isPaid();
}
```

<a name="on-demand-notifications"></a>
### On-Demand Notifications

Sometimes you may need to send a notification to someone who is not stored as a "user" of your application. Using `AnonymousNotifiable`, you may specify ad-hoc notification routing information before sending the notification:

```php
use Crustum\Notification\AnonymousNotifiable;
use App\Notification\InvoicePaid;

$anonymous = new AnonymousNotifiable();
$anonymous
    ->route('mail', 'taylor@example.com')
    ->route('seven', '5555555555')
    ->route('slack', '#slack-channel')
    ->notify(new InvoicePaid($invoice));
```

If you would like to provide the recipient's name when sending an on-demand notification to the `mail` route, you may provide an array that contains the email address as the key and the name as the value:

```php
$anonymous->route('mail', ['barrett@example.com' => 'Barrett Blair']);
```

You can use `NotificationManager::route()` as a shorthand:

```php
use Crustum\Notification\NotificationManager;

NotificationManager::route('mail', 'taylor@example.com')
    ->route('seven', '5555555555')
    ->route('slack', '#slack-channel')
    ->notify(new InvoicePaid($invoice));
```

<a name="mail-notifications"></a>
## Mail Notifications

<a name="formatting-mail-messages"></a>
### Formatting Mail Messages

If a notification supports being sent as an email, you should define a `toMail()` method on the notification class. This method will receive a `$notifiable` entity and should return a `Crustum\Notification\Message\MailMessage` instance.

The `MailMessage` class contains a few simple methods to help you build transactional email messages. Mail messages may contain lines of text as well as a "call to action". Let's take a look at an example `toMail()` method:

```php
use Crustum\Notification\Message\MailMessage;

/**
 * Get the mail representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return \Crustum\Notification\Message\MailMessage
 */
public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
{
    $url = \Cake\Routing\Router::url(
        ['controller' => 'Invoices', 'action' => 'view', $this->invoice->id],
        true
    );

    return MailMessage::create()
        ->greeting('Hello!')
        ->line('One of your invoices has been paid!')
        ->action('View Invoice', $url)
        ->line('Thank you for using our application!');
}
```

> [!NOTE]
> Note we are using `$this->invoice->id` in our `toMail()` method. You may pass any data your notification needs to generate its message into the notification's constructor.

In this example, we register a greeting, a line of text, a call to action, and then another line of text. These methods provided by the `MailMessage` object make it simple and fast to format small transactional emails. The mail channel will then translate the message components into a beautiful, responsive HTML email template with a plain-text counterpart.

> [!NOTE]
> When sending mail notifications, be sure to set the configuration in your `config/app.php` configuration file. This value will be used in the header and footer of your mail notification messages.

#### Error Messages

Some notifications inform users of errors, such as a failed invoice payment. You may indicate that a mail message is regarding an error by calling the `error()` method when building your message. When using the `error()` method on a mail message, the call to action button will be red instead of blue:

```php
/**
 * Get the mail representation of the notification.
 */
public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
{
    return MailMessage::create()
        ->error()
        ->subject('Invoice Payment Failed')
        ->line('Your invoice payment has failed.');
}
```

#### Other Mail Notification Formatting Options

Instead of defining the "lines" of text in the notification class, you may use the `view()` method to specify a custom template that should be used to render the notification email:

```php
/**
 * Get the mail representation of the notification.
 */
public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
{
    return MailMessage::create()
        ->view('email/invoice_paid', ['invoice' => $this->invoice]);
}
```

<a name="customizing-the-sender"></a>
### Customizing the Sender

By default, the email's sender / from address is defined in the `config/app.php` configuration file. However, you may specify the from address for a specific notification using the `from()` method:

```php
/**
 * Get the mail representation of the notification.
 */
public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
{
    return MailMessage::create()
        ->from('barrett@example.com', 'Barrett Blair')
        ->line('Your invoice has been paid.');
}
```

<a name="customizing-the-recipient"></a>
### Customizing the Recipient

When sending notifications via the `mail` channel, the notification system will automatically look for an `email` property on your notifiable entity. You may customize which email address is used to deliver the notification by defining a `routeNotificationForMail()` method on the notifiable entity:

```php
<?php
namespace App\Model\Entity;

use Crustum\Notification\Notification;
use Cake\ORM\Entity;

class User extends Entity
{
    /**
     * Route notifications for the mail channel.
     *
     * @param \Crustum\Notification\Notification $notification
     * @return array|string
     */
    public function routeNotificationForMail(Notification $notification): array|string
    {
        // Return email address only...
        return $this->email_address;

        // Return email address and name...
        return [$this->email_address => $this->name];
    }
}
```

<a name="customizing-the-subject"></a>
### Customizing the Subject

By default, the email's subject is the class name of the notification formatted to "Title Case". So, if your notification class is named `InvoicePaid`, the email's subject will be `Invoice Paid`. If you would like to specify a different subject for the message, you may call the `subject()` method when building your message:

```php
/**
 * Get the mail representation of the notification.
 */
public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
{
    return MailMessage::create()
        ->subject('Notification Subject')
        ->line('Your invoice has been paid.');
}
```

<a name="customizing-the-templates"></a>
### Customizing the Templates

You can modify the HTML and plain-text template used by mail notifications by creating custom templates in your application's `templates/email` directory.

<a name="mail-attachments"></a>
### Attachments

To add attachments to an email notification, use the `attach()` method while building your message. The `attach()` method accepts the absolute path to the file as its first argument:

```php
/**
 * Get the mail representation of the notification.
 */
public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
{
    return MailMessage::create()
        ->greeting('Hello!')
        ->attach('/path/to/file');
}
```

When attaching files to a message, you may also specify the display name and / or MIME type by passing an array as the second argument to the `attach()` method:

```php
/**
 * Get the mail representation of the notification.
 */
public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
{
    return MailMessage::create()
        ->greeting('Hello!')
        ->attach('/path/to/file', [
            'as' => 'name.pdf',
            'mime' => 'application/pdf',
        ]);
}
```

<a name="using-custom-templates"></a>
### Using Custom Templates

If needed, you may specify a custom view template for your notification email:

```php
/**
 * Get the mail representation of the notification.
 */
public function toMail(EntityInterface|AnonymousNotifiable $notifiable): MailMessage
{
    return MailMessage::create()
        ->view('email/custom_notification', [
            'invoice' => $this->invoice,
            'user' => $notifiable,
        ]);
}
```

<a name="database-notifications"></a>
## Database Notifications

<a name="database-prerequisites"></a>
### Prerequisites

The `database` notification channel stores the notification information in a database table. This table will contain information such as the notification type as well as a JSON data structure that describes the notification.

You can query the table to display the notifications in your application's user interface. But, before you can do that, you will need to create a database table to hold your notifications. You can use migrations to create the proper table schema:

```shell
bin/cake migrations migrate -p Crustum/Notification
```

<a name="formatting-database-notifications"></a>
### Formatting Database Notifications

If a notification supports being stored in a database table, you should define a `toDatabase()` or `toArray()` method on the notification class. This method will receive a `$notifiable` entity and should return a plain PHP array or a `DatabaseMessage` instance. The returned array will be encoded as JSON and stored in the `data` column of your `notifications` table. Let's take a look at an example `toArray()` method:

```php
/**
 * Get the array representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return array<string, mixed>
 */
public function toArray(EntityInterface|AnonymousNotifiable $notifiable): array
{
    return [
        'invoice_id' => $this->invoice->id,
        'amount' => $this->invoice->amount,
    ];
}
```

Alternatively, you can use the fluent `DatabaseMessage` API:

```php
use Crustum\Notification\Message\DatabaseMessage;
use Crustum\Notification\Message\Action;

/**
 * Get the database representation of the notification.
 *
 * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
 * @return \Crustum\Notification\Message\DatabaseMessage
 */
public function toDatabase(EntityInterface|AnonymousNotifiable $notifiable): DatabaseMessage
{
    return DatabaseMessage::new()
        ->title('Order Shipped')
        ->message('Your order #' . $this->order->id . ' has been shipped')
        ->type('success')
        ->actionUrl(['controller' => 'Orders', 'action' => 'view', $this->order->id])
        ->icon('truck')
        ->addAction(
            Action::new('view')
                ->label('View Order')
                ->url(['controller' => 'Orders', 'action' => 'view', $this->order->id])
                ->type('primary')
        );
}
```

The fluent API provides better IDE autocomplete and type safety. You can still use arrays by calling `->data()`:

```php
return DatabaseMessage::new()->data([
    'invoice_id' => $this->invoice->id,
    'amount' => $this->invoice->amount,
]);
```

When a notification is stored in your application's database, the `type` column will be populated with the notification's class name, and the `read_at` column will be `null`.

<a name="accessing-the-notifications"></a>
### Accessing the Notifications

Once notifications are stored in the database, you need a convenient way to access them from your notifiable entities. The `NotifiableBehavior` includes a `Notifications` association that returns the notifications for the entity. To fetch notifications, you may access this association like any other CakePHP association. By default, notifications will be sorted by the `created` timestamp with the most recent notifications at the beginning of the collection:

```php
$usersTable = $this->getTableLocator()->get('Users');
$user = $usersTable->get(1, contain: ['Notifications']);

foreach ($user->notifications as $notification) {
    echo $notification->type;
}
```

If you want to retrieve only the "unread" notifications, you may use the `unreadNotifications()` method provided by the behavior. This method returns a query object that you can further customize or execute:

```php
$usersTable = $this->getTableLocator()->get('Users');
$user = $usersTable->get(1);

$unreadQuery = $usersTable->unreadNotifications($user);
$unreadNotifications = $unreadQuery->all();

foreach ($unreadNotifications as $notification) {
    echo $notification->type;
}
```

You can also chain additional query methods:

```php
$recentUnread = $usersTable->unreadNotifications($user)
    ->limit(10)
    ->order(['created' => 'DESC'])
    ->all();
```

If you want to retrieve only the "read" notifications, you may use the `readNotifications()` method:

```php
$readQuery = $usersTable->readNotifications($user);
$readNotifications = $readQuery->all();

foreach ($readNotifications as $notification) {
    echo $notification->type;
}
```

<a name="marking-notifications-as-read"></a>
### Marking Notifications as Read

Typically, you will want to mark a notification as "read" when a user views it. The `NotifiableBehavior` provides a `markNotificationAsRead()` method, which updates the `read_at` column on the notification's database record. This method is called on the table and receives the entity and notification ID:

```php
$usersTable = $this->getTableLocator()->get('Users');
$user = $usersTable->get(1);

$usersTable->markNotificationAsRead($user, $notificationId);
```

If you need to mark all notifications for a user as read, you may use the `markAllNotificationsAsRead()` method:

```php
$usersTable->markAllNotificationsAsRead($user);
```

You may also delete notifications directly through the Notifications table:

```php
$notificationsTable = $this->getTableLocator()->get('Crustum/Notification.Notifications');
$notificationsTable->deleteAll([
    'model' => 'Users',
    'foreign_key' => $user->id
]);
```

<a name="notification-ui"></a>
## Notification UI

<a name="notification-ui-installation"></a>
### Installation

The NotificationUI plugin provides a modern, modular JavaScript interface for displaying notifications to users. Install it via Composer:

```shell
composer require crustum/notification-ui
```

Load the plugin in your `Application.php`:

```php
$this->addPlugin('Crustum/NotificationUI');
```

<a name="notification-ui-usage"></a>
### Usage

Add the notification bell to your layout:

```php
<?php $authUser = $this->request->getAttribute('identity'); ?>

<li class="nav-item">
    <?= $this->element('Crustum/NotificationUI.notifications/bell_icon', [
        'mode' => 'panel', // or 'dropdown'
    ]) ?>
</li>
```

The notification bell will automatically poll the server for new notifications, display an unread count badge, show notifications in a dropdown or side panel, allow marking notifications as read, and support action buttons.

<a name="notification-ui-configuration"></a>
### Configuration Options

Customize the notification widget behavior:

```php
<?= $this->element('Crustum/NotificationUI.notifications/bell_icon', [
    'mode' => 'panel',          // 'dropdown' or 'panel'
    'position' => 'right',      // 'left' or 'right' (dropdown only)
    'theme' => 'dark',          // 'light' or 'dark'
    'pollInterval' => 60000,    // Poll every 60 seconds
    'enablePolling' => true,    // Enable/disable database polling
    'perPage' => 20,            // Notifications per page
]) ?>
```

**Display Modes:**
- **Dropdown**: Traditional dropdown menu attached to the bell icon
- **Panel**: Sticky side panel (like Filament Notifications)

<a name="notification-ui-broadcasting"></a>
### Real-Time Broadcasting

Enable WebSocket broadcasting for instant notification delivery:

```php
<?php $authUser = $this->request->getAttribute('identity'); ?>

<?= $this->element('Crustum/NotificationUI.notifications/bell_icon', [
    'mode' => 'panel',
    'enablePolling' => true,    // Keep database polling
    'broadcasting' => [         // Add real-time support
        'userId' => $authUser->getIdentifier(),
        'userName' => $authUser->username ?? 'User',
        'pusherKey' => 'app-key',
        'pusherHost' => '127.0.0.1',
        'pusherPort' => 8080,
        'pusherCluster' => 'mt1',
    ],
]) ?>
```

With broadcasting enabled, notifications are delivered instantly via WebSocket while still being persisted in the database. This provides the best user experience with reliable fallback.

> [!NOTE]
> Broadcasting requires the `crustum/broadcasting-notification` plugin to be installed and configured. See the [Broadcasting Notifications](modules.md#broadcasting-notifications) documentation for more details.

<a name="localizing-notifications"></a>
## Localizing Notifications

CakePHP Notification Plugin allows you to send notifications in a locale other than the current locale, and will even remember this locale if the notification is queued.

To accomplish this, you may use the `locale()` method to set the desired language:

```php
$usersTable = $this->getTableLocator()->get('Users');
$user = $usersTable->get(1);

$usersTable->notify($user, (new InvoicePaid($invoice))->locale('es'));
```

Localization of multiple notifiable entries may also be achieved via the `NotificationManager`:

```php
NotificationManager::locale('es')->send($users, new InvoicePaid($invoice));
```

### User Preferred Locales

Sometimes, applications store each user's preferred locale. By implementing a `preferredLocale` property or method on your notifiable entity, you may instruct the notification system to use this stored locale when sending a notification:

```php
<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class User extends Entity
{
    /**
     * Get the user's preferred locale.
     *
     * @return string
     */
    public function getPreferredLocale(): string
    {
        return $this->locale;
    }
}
```

Once you have defined the property or method, the system will automatically use the preferred locale when sending notifications to the entity. Therefore, there is no need to call the `locale()` method when using this feature:

```php
$usersTable = $this->getTableLocator()->get('Users');
$user = $usersTable->get(1);

$usersTable->notify($user, new InvoicePaid($invoice));
```

<a name="testing"></a>
## Testing

You may use the `\Crustum\Notification\TestSuite\NotificationTrait` to prevent notifications from being sent during testing. Typically, sending notifications is unrelated to the code you are actually testing. Most likely, it is sufficient to simply assert that your application was instructed to send a given notification.

After adding the `NotificationTrait` to your test case, you may then assert that notifications were instructed to be sent to users and even inspect the data the notifications received:

```php
<?php
namespace App\Test\TestCase;

use App\Notification\InvoicePaid;
use Crustum\Notification\TestSuite\NotificationTrait;
use Cake\TestSuite\TestCase;

class ExampleTest extends TestCase
{
    use NotificationTrait;

    protected array $fixtures = ['app.Users', 'app.Invoices'];

    public function testInvoicesCanBePaid(): void
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new InvoicePaid(123, 99.99));

        $this->assertNoNotificationsSent();

        $this->assertNotificationSentTo(
            $user,
            InvoicePaid::class
        );

        $this->assertNotificationNotSent(AnotherNotification::class);

        $this->assertNotificationSentTimes(InvoicePaid::class, 1);

        $this->assertNotificationCount(1);
    }
}
```

You may pass a closure as the second argument to the `assertNotificationSentTo` method in order to assert that a notification was sent that passes a given "truth test". If at least one notification was sent that passes the given truth test then the assertion will be successful:

```php
$this->assertNotificationSentTo(
    $user,
    function (InvoicePaid $notification) use ($invoice) {
        return $notification->invoiceId === $invoice->id;
    }
);
```

When you use the `NotificationTrait`, all notifications are captured instead of being sent, allowing you to make assertions. The trait provides several helper methods to inspect captured notifications:

```php
use App\Notification\InvoicePaid;

public function testNotificationDetails(): void
{
    $usersTable = $this->getTableLocator()->get('Users');
    $user = $usersTable->get(1);

    $usersTable->notify($user, new InvoicePaid(123, 99.99));

    $notifications = $this->getNotificationsByClass(InvoicePaid::class);
    $this->assertCount(1, $notifications);

    $notificationData = $notifications[0];
    $this->assertEquals(InvoicePaid::class, $notificationData['notification_class']);
    $this->assertEquals($user->id, $notificationData['notifiable_id']);
}
```

<a name="asserting-notifications-sent-to-specific-users"></a>
### Asserting Notifications Sent to Specific Users

When testing, you often need to verify a notification was sent to a particular user. The `assertNotificationSentTo` method makes this simple:

```php
use App\Notification\InvoicePaid;

public function testNotificationSentToUser(): void
{
    $usersTable = $this->getTableLocator()->get('Users');
    $user = $usersTable->get(1);

    $usersTable->notify($user, new InvoicePaid(123, 99.99));

    $this->assertNotificationSentTo($user, InvoicePaid::class);
}
```

You can verify multiple users received the same notification:

```php
public function testNotificationSentToMultipleUsers(): void
{
    $usersTable = $this->getTableLocator()->get('Users');
    $user1 = $usersTable->get(1);
    $user2 = $usersTable->get(2);

    $usersTable->notify($user1, new InvoicePaid(123, 99.99));
    $usersTable->notify($user2, new InvoicePaid(456, 149.99));

    $this->assertNotificationSentTo($user1, InvoicePaid::class);
    $this->assertNotificationSentTo($user2, InvoicePaid::class);
    $this->assertNotificationSentTimes(InvoicePaid::class, 2);
}
```

Or verify a notification was not sent to a user:

```php
public function testNotificationNotSentToInactiveUser(): void
{
    $usersTable = $this->getTableLocator()->get('Users');
    $activeUser = $usersTable->get(1);
    $inactiveUser = $usersTable->get(2);

    $usersTable->notify($activeUser, new InvoicePaid(123, 99.99));

    $this->assertNotificationSentTo($activeUser, InvoicePaid::class);
    $this->assertNotificationNotSentTo($inactiveUser, InvoicePaid::class);
}
```

<a name="asserting-notification-channels"></a>
### Asserting Notification Channels

You can assert that a notification was sent through specific channels:

```php
public function testNotificationSentThroughChannels(): void
{
    $usersTable = $this->getTableLocator()->get('Users');
    $user = $usersTable->get(1);

    $usersTable->notify($user, new InvoicePaid(123, 99.99));

    $this->assertNotificationSentToChannel('database', InvoicePaid::class);
    $this->assertNotificationSentToChannel('mail', InvoicePaid::class);
}
```

This is particularly useful when your notification's `via` method determines channels dynamically:

```php
public function testVipUsersGetSlackNotifications(): void
{
    $usersTable = $this->getTableLocator()->get('Users');
    $vipUser = $usersTable->get(1); // User with is_vip = true
    $regularUser = $usersTable->get(2); // User with is_vip = false

    $usersTable->notify($vipUser, new ImportantAlert());
    $usersTable->notify($regularUser, new ImportantAlert());

    $this->assertNotificationSentToChannel('slack', ImportantAlert::class);

    $vipNotifications = $this->getNotificationsFor($vipUser, ImportantAlert::class);
    $this->assertContains('slack', $vipNotifications[0]['channels']);

    $regularNotifications = $this->getNotificationsFor($regularUser, ImportantAlert::class);
    $this->assertNotContains('slack', $regularNotifications[0]['channels']);
}
```

<a name="inspecting-notification-content"></a>
### Inspecting Notification Content

Sometimes you need to verify the specific content or data contained in a notification. The `NotificationTrait` provides several methods to retrieve and inspect captured notifications:

```php
public function testNotificationContainsCorrectData(): void
{
    $usersTable = $this->getTableLocator()->get('Users');
    $user = $usersTable->get(1);

    $usersTable->notify($user, new InvoicePaid(123, 99.99));

    $this->assertNotificationDataContains(
        InvoicePaid::class,
        'invoice_id',
        123
    );

    $notifications = $this->getNotificationsByClass(InvoicePaid::class);
    $notification = $notifications[0]['notification'];

    $this->assertEquals(123, $notification->invoiceId);
    $this->assertEquals(99.99, $notification->amount);
}
```

For channel-specific content, you can call the notification's channel methods directly:

```php
public function testMailNotificationContent(): void
{
    $usersTable = $this->getTableLocator()->get('Users');
    $user = $usersTable->get(1);

    $usersTable->notify($user, new InvoicePaid(123, 99.99));

    $notifications = $this->getNotificationsByClass(InvoicePaid::class);
    $notification = $notifications[0]['notification'];

    $mailMessage = $notification->toMail($user);

    $this->assertEquals('Invoice Paid', $mailMessage->subject);
    $this->assertNotEmpty($mailMessage->introLines);
    $this->assertStringContainsString('Invoice #123', $mailMessage->introLines[0]);
}
```

Testing Slack message structure:

```php
public function testSlackNotificationFormat(): void
{
    $usersTable = $this->getTableLocator()->get('Users');
    $user = $usersTable->get(1);

    $usersTable->notify($user, new InvoicePaid(123, 99.99));

    $notifications = $this->getNotificationsByClass(InvoicePaid::class);
    $notification = $notifications[0]['notification'];

    $slackMessage = $notification->toSlack($user);
    $payload = $slackMessage->toArray();

    $this->assertArrayHasKey('blocks', $payload);
    $this->assertArrayHasKey('text', $payload);
    $this->assertStringContainsString('Invoice', $payload['text']);
}
```

<a name="on-demand-notifications"></a>
### On-Demand Notifications

If the code you are testing sends [on-demand notifications](#on-demand-notifications), you can test that the on-demand notification was sent via the `assertOnDemandNotificationSent` method:

```php
use Crustum\Notification\NotificationManager;

public function testOnDemandNotificationSent(): void
{
    NotificationManager::route(['slack' => '#general'])
        ->notify(new ServerAlert('Server down'));

    $this->assertOnDemandNotificationSent(ServerAlert::class);
}
```

You can also inspect on-demand notifications using the `getOnDemandNotifications` method:

```php
public function testOnDemandNotificationContent(): void
{
    NotificationManager::route(['slack' => '#general'])
        ->notify(new ServerAlert('Server down'));

    $onDemandNotifications = $this->getOnDemandNotifications();
    $this->assertCount(1, $onDemandNotifications);

    $notification = array_values($onDemandNotifications)[0];
    $this->assertEquals(ServerAlert::class, $notification['notification_class']);
    $this->assertContains('slack', $notification['channels']);
}
```

<a name="testing-mail-with-emailtrait"></a>
### Testing Mail with EmailTrait

For more detailed email testing, you can combine `NotificationTrait` with CakePHP's `EmailTrait`:

```php
use Crustum\Notification\TestSuite\NotificationTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;

class NotificationTest extends TestCase
{
    use NotificationTrait;
    use EmailTrait;

    protected array $fixtures = ['app.Users'];

    public function testEmailNotificationIsSent(): void
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get(1);

        $usersTable->notify($user, new InvoicePaid(123, 99.99));

        $this->assertNotificationSentTo($user, InvoicePaid::class);
        $this->assertNotificationSentToChannel('mail', InvoicePaid::class);

        $this->assertMailSentTo($user->email);
        $this->assertMailSubjectContains('Invoice Paid');
        $this->assertMailContains('Invoice #123');
        $this->assertMailContains('$99.99');
    }
}
```

<a name="available-assertions"></a>
### Available Assertions

The `NotificationTrait` provides the following assertion methods for your tests:

| Method | Description |
|--------|-------------|
| `assertNotificationSent(string $class)` | Assert a notification of the given class was sent |
| `assertNotificationNotSent(string $class)` | Assert a notification was not sent |
| `assertNotificationSentTo($notifiable, string $class)` | Assert a notification was sent to a specific entity |
| `assertNotificationNotSentTo($notifiable, string $class)` | Assert a notification was not sent to an entity |
| `assertNotificationSentToChannel(string $channel, string $class)` | Assert a notification was sent through a channel |
| `assertNotificationSentTimes(string $class, int $times)` | Assert a notification was sent a specific number of times |
| `assertNotificationSentToTimes($notifiable, string $class, int $times)` | Assert a notification was sent to an entity N times |
| `assertNotificationCount(int $count)` | Assert the total number of notifications sent |
| `assertNoNotificationsSent()` | Assert no notifications were sent |
| `assertOnDemandNotificationSent(string $class)` | Assert an on-demand notification was sent |
| `assertNotificationDataContains(string $class, string $key, mixed $value)` | Assert notification contains specific data |

Helper methods for retrieving captured notifications:

| Method | Description |
|--------|-------------|
| `getNotifications()` | Get all captured notifications |
| `getNotificationsByClass(string $class)` | Get notifications of a specific class |
| `getNotificationsFor($notifiable, string $class)` | Get notifications sent to a specific entity |
| `getNotificationsByChannel(string $channel)` | Get notifications sent through a channel |
| `getOnDemandNotifications()` | Get on-demand notifications |

<a name="notification-events"></a>
## Notification Events

### Notification Sending Event

When a notification is sending, the `Model.Notification.sending` event is dispatched by the notification system. This contains the "notifiable" entity and the notification instance itself. You may register event listeners for this event in your application's bootstrap or in your Table classes:

```php
use Cake\Event\Event;
use Cake\Event\EventManager;

EventManager::instance()->on('Model.Notification.sending', function (Event $event) {
    $notifiable = $event->getData('notifiable');
    $notification = $event->getData('notification');
    $channel = $event->getData('channel');

    // ...
});
```

The notification will not be sent if an event listener returns `false`:

```php
EventManager::instance()->on('Model.Notification.sending', function (Event $event) {
    // Check some condition
    if ($shouldNotSend) {
        $event->stopPropagation();
        return false;
    }
});
```

### Notification Sent Event

When a notification is sent, the `Model.Notification.sent` event is dispatched by the notification system. This contains the "notifiable" entity and the notification instance itself:

```php
use Cake\Event\Event;
use Cake\Event\EventManager;

EventManager::instance()->on('Model.Notification.sent', function (Event $event) {
    $notifiable = $event->getData('notifiable');
    $notification = $event->getData('notification');
    $channel = $event->getData('channel');
    $response = $event->getData('response');

    // Log the notification...
});
```

### Notification Failed Event

When a notification fails to send, the `Model.Notification.failed` event is dispatched:

```php
use Cake\Event\Event;
use Cake\Event\EventManager;

EventManager::instance()->on('Model.Notification.failed', function (Event $event) {
    $notifiable = $event->getData('notifiable');
    $notification = $event->getData('notification');
    $channel = $event->getData('channel');
    $exception = $event->getData('exception');

    // Log the failure...
});
```

<a name="custom-channels"></a>
## Custom Channels

CakePHP Notification Plugin ships with a handful of notification channels, but you may want to write your own channels to deliver notifications via other services. CakePHP makes it simple. To get started, define a class that implements the `Crustum\Notification\Channel\ChannelInterface`. The interface requires a `send()` method:

```php
<?php
namespace App\Notification\Channel;

use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Channel\ChannelInterface;
use Crustum\Notification\Notification;

class VoiceChannel implements ChannelInterface
{
    /**
     * Send the given notification.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
     * @param \Crustum\Notification\Notification $notification
     * @return mixed
     */
    public function send(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): mixed
    {
        $message = $notification->toVoice($notifiable);

        // Send notification to the $notifiable instance...

        return $response;
    }
}
```

Once your notification channel class has been defined, you need to register it with the `NotificationManager`. This is typically done in your application's bootstrap:

```php
use Crustum\Notification\NotificationManager;
use App\Notification\Channel\VoiceChannel;

NotificationManager::setChannel('voice', new VoiceChannel());
```

Now you may return the channel name from the `via()` method of any of your notifications:

```php
<?php
namespace App\Notification;

use App\Notification\Message\VoiceMessage;
use Cake\Datasource\EntityInterface;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Notification;

class InvoicePaid extends Notification
{
    /**
     * Get the notification channels.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
     * @return array<string>
     */
    public function via(EntityInterface|AnonymousNotifiable $notifiable): array
    {
        return ['voice'];
    }

    /**
     * Get the voice representation of the notification.
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable
     * @return \App\Notification\Message\VoiceMessage
     */
    public function toVoice(EntityInterface|AnonymousNotifiable $notifiable): VoiceMessage
    {
        // Return your voice message object
        return new VoiceMessage('Your invoice has been paid');
    }
}
```

Alternatively, you can register channels using a provider class. Create a provider class that implements `Crustum\Notification\Provider\ChannelProviderInterface`:

```php
<?php
namespace App\Notification\Provider;

use App\Notification\Channel\VoiceChannel;
use Crustum\Notification\Provider\ChannelProviderInterface;
use Crustum\Notification\Registry\ChannelRegistry;

class VoiceChannelProvider implements ChannelProviderInterface
{
    /**
     * Register the channel.
     *
     * @param \Crustum\Notification\Registry\ChannelRegistry $registry
     * @return void
     */
    public function register(ChannelRegistry $registry): void
    {
        $registry->add('voice', new VoiceChannel());
    }
}
```

Then register your provider in the plugin's bootstrap:

```php
use Cake\Event\EventManager;
use App\Notification\Provider\VoiceChannelProvider;

EventManager::instance()->on('Notification.Registry.discover', function ($event) {
    $registry = $event->getSubject();
    (new VoiceChannelProvider())->register($registry);
});
```
