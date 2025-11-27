<?php
declare(strict_types=1);

namespace Crustum\Notification\Channel;

use Cake\Datasource\EntityInterface;
use Cake\Mailer\Mailer;
use Crustum\Notification\AnonymousNotifiable;
use Crustum\Notification\Message\MailMessage;
use Crustum\Notification\Notification;

/**
 * Mail Channel
 *
 * Sends notifications via email using CakePHP's Mailer component.
 */
class MailChannel implements ChannelInterface
{
    /**
     * Channel configuration
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param array<string, mixed> $config Channel configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Send the notification via email
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The entity receiving the notification
     * @param \Crustum\Notification\Notification $notification The notification to send
     * @return array<string, string>|null Email result or null if not sent
     */
    public function send(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): mixed
    {
        $message = $notification->toMail($notifiable);

        if (!$message) {
            return null;
        }

        $recipients = $this->getRecipients($notifiable, $notification);

        if (empty($recipients)) {
            return null;
        }

        return $this->sendMessage($message, $recipients, $notifiable);
    }

    /**
     * Get recipients for the notification
     *
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @param \Crustum\Notification\Notification $notification The notification
     * @return array<string> Array of email addresses
     */
    protected function getRecipients(EntityInterface|AnonymousNotifiable $notifiable, Notification $notification): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            $route = $notifiable->routeNotificationFor('mail', $notification);
            if ($route) {
                if (is_string($route)) {
                    return [$route];
                }
                if (is_array($route)) {
                    $recipients = [];
                    foreach ($route as $email => $name) {
                        if (is_numeric($email)) {
                            $recipients[] = $name;
                        } else {
                            $recipients[] = $email;
                        }
                    }

                    return $recipients;
                }
            }

            return [];
        }

        if (method_exists($notifiable, 'routeNotificationForMail')) {
            $route = $notifiable->routeNotificationForMail($notification);
            if ($route) {
                return is_array($route) ? $route : [$route];
            }
        }

        if (isset($notifiable->email)) {
            return [$notifiable->email];
        }

        return [];
    }

    /**
     * Send the email message
     *
     * @param \Crustum\Notification\Message\MailMessage|string $message The message
     * @param array<string> $recipients Recipient email addresses
     * @param \Cake\Datasource\EntityInterface|\Crustum\Notification\AnonymousNotifiable $notifiable The notifiable entity
     * @return array<string, string> Email send result
     */
    protected function sendMessage(MailMessage|string $message, array $recipients, EntityInterface|AnonymousNotifiable $notifiable): array
    {
        $mailer = new Mailer($this->config['profile'] ?? 'default');

        if ($message instanceof MailMessage) {
            $this->configureMailer($mailer, $message, $recipients);
        } elseif (is_string($message)) {
            $mailer->setTo($recipients)
                ->setSubject($message)
                ->deliver($message);
        }

        return $mailer->send();
    }

    /**
     * Configure mailer from MailMessage
     *
     * @param \Cake\Mailer\Mailer $mailer The mailer instance
     * @param \Crustum\Notification\Message\MailMessage $message The mail message
     * @param array<string> $recipients Recipients
     * @return void
     */
    protected function configureMailer(Mailer $mailer, MailMessage $message, array $recipients): void
    {
        $mailer->setTo($recipients);

        if ($message->subject) {
            $mailer->setSubject($message->subject);
        }

        if ($message->from) {
            $mailer->setFrom($message->from['address'], $message->from['name'] ?? null);
        }

        if ($message->replyTo) {
            foreach ($message->replyTo as $replyTo) {
                $mailer->setReplyTo($replyTo['address'], $replyTo['name'] ?? null);
            }
        }

        if ($message->cc) {
            foreach ($message->cc as $cc) {
                $mailer->setCc($cc['address'], $cc['name'] ?? null);
            }
        }

        if ($message->bcc) {
            foreach ($message->bcc as $bcc) {
                $mailer->setBcc($bcc['address'], $bcc['name'] ?? null);
            }
        }

        if ($message->attachments) {
            foreach ($message->attachments as $attachment) {
                $mailer->setAttachments([$attachment['file'] => $attachment['options']]);
            }
        }

        if ($message->view) {
            $mailer->viewBuilder()->setTemplate($message->view);
            $mailer->setViewVars($message->viewData);
        } else {
            $mailer->viewBuilder()
                ->setTemplate('Crustum/Notification.notification')
                ->setLayout('Crustum/Notification.default');

            $mailer->setViewVars([
                'level' => $message->level,
                'greeting' => $message->greeting,
                'introLines' => $message->introLines,
                'actionText' => $message->actionText,
                'actionUrl' => $message->actionUrl,
                'outroLines' => $message->outroLines,
                'salutation' => $message->salutation,
            ]);
        }
    }
}
