<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Message;

use Cake\TestSuite\TestCase;
use Crustum\Notification\Message\MailMessage;

/**
 * MailMessage Test Case
 *
 * Tests the mail message fluent builder
 */
class MailMessageTest extends TestCase
{
    /**
     * Test create factory method
     *
     * @return void
     */
    public function testCreate(): void
    {
        $message = MailMessage::create();

        $this->assertInstanceOf(MailMessage::class, $message);
    }

    /**
     * Test subject method
     *
     * @return void
     */
    public function testSubject(): void
    {
        $message = MailMessage::create()
            ->subject('Test Subject');

        $this->assertEquals('Test Subject', $message->subject);
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $message = MailMessage::create()
            ->view('custom/template', ['key' => 'value']);

        $this->assertEquals('custom/template', $message->view);
        $this->assertEquals(['key' => 'value'], $message->viewData);
    }

    /**
     * Test level methods
     *
     * @return void
     */
    public function testLevel(): void
    {
        $message = MailMessage::create()
            ->level('custom');

        $this->assertEquals('custom', $message->level);
    }

    /**
     * Test success method
     *
     * @return void
     */
    public function testSuccess(): void
    {
        $message = MailMessage::create()
            ->success();

        $this->assertEquals('success', $message->level);
    }

    /**
     * Test error method
     *
     * @return void
     */
    public function testError(): void
    {
        $message = MailMessage::create()
            ->error();

        $this->assertEquals('error', $message->level);
    }

    /**
     * Test warning method
     *
     * @return void
     */
    public function testWarning(): void
    {
        $message = MailMessage::create()
            ->warning();

        $this->assertEquals('warning', $message->level);
    }

    /**
     * Test greeting method
     *
     * @return void
     */
    public function testGreeting(): void
    {
        $message = MailMessage::create()
            ->greeting('Hello User');

        $this->assertEquals('Hello User', $message->greeting);
    }

    /**
     * Test salutation method
     *
     * @return void
     */
    public function testSalutation(): void
    {
        $message = MailMessage::create()
            ->salutation('Best Regards');

        $this->assertEquals('Best Regards', $message->salutation);
    }

    /**
     * Test line method adds to intro before action
     *
     * @return void
     */
    public function testLineAddsToIntro(): void
    {
        $message = MailMessage::create()
            ->line('First line')
            ->line('Second line');

        $this->assertEquals(['First line', 'Second line'], $message->introLines);
        $this->assertEmpty($message->outroLines);
    }

    /**
     * Test line method adds to outro after action
     *
     * @return void
     */
    public function testLineAddsToOutroAfterAction(): void
    {
        $message = MailMessage::create()
            ->line('Intro line')
            ->action('Click', 'https://example.com')
            ->line('Outro line');

        $this->assertEquals(['Intro line'], $message->introLines);
        $this->assertEquals(['Outro line'], $message->outroLines);
    }

    /**
     * Test lineIf method with true condition
     *
     * @return void
     */
    public function testLineIfWithTrueCondition(): void
    {
        $message = MailMessage::create()
            ->lineIf(true, 'This line should appear');

        $this->assertEquals(['This line should appear'], $message->introLines);
    }

    /**
     * Test lineIf method with false condition
     *
     * @return void
     */
    public function testLineIfWithFalseCondition(): void
    {
        $message = MailMessage::create()
            ->lineIf(false, 'This line should not appear');

        $this->assertEmpty($message->introLines);
    }

    /**
     * Test action method
     *
     * @return void
     */
    public function testAction(): void
    {
        $message = MailMessage::create()
            ->action('Click Here', 'https://example.com');

        $this->assertEquals('Click Here', $message->actionText);
        $this->assertEquals('https://example.com', $message->actionUrl);
    }

    /**
     * Test from method
     *
     * @return void
     */
    public function testFrom(): void
    {
        $message = MailMessage::create()
            ->from('sender@example.com', 'Sender Name');

        $this->assertEquals([
            'address' => 'sender@example.com',
            'name' => 'Sender Name',
        ], $message->from);
    }

    /**
     * Test replyTo method
     *
     * @return void
     */
    public function testReplyTo(): void
    {
        $message = MailMessage::create()
            ->replyTo('reply@example.com', 'Reply Name');

        $this->assertEquals([[
            'address' => 'reply@example.com',
            'name' => 'Reply Name',
        ]], $message->replyTo);
    }

    /**
     * Test multiple replyTo addresses
     *
     * @return void
     */
    public function testMultipleReplyTo(): void
    {
        $message = MailMessage::create()
            ->replyTo('reply1@example.com')
            ->replyTo('reply2@example.com', 'Second Reply');

        $this->assertCount(2, $message->replyTo);
    }

    /**
     * Test cc method
     *
     * @return void
     */
    public function testCc(): void
    {
        $message = MailMessage::create()
            ->cc('cc@example.com', 'CC Name');

        $this->assertEquals([[
            'address' => 'cc@example.com',
            'name' => 'CC Name',
        ]], $message->cc);
    }

    /**
     * Test bcc method
     *
     * @return void
     */
    public function testBcc(): void
    {
        $message = MailMessage::create()
            ->bcc('bcc@example.com', 'BCC Name');

        $this->assertEquals([[
            'address' => 'bcc@example.com',
            'name' => 'BCC Name',
        ]], $message->bcc);
    }

    /**
     * Test attach method
     *
     * @return void
     */
    public function testAttach(): void
    {
        $message = MailMessage::create()
            ->attach('/path/to/file.pdf', ['as' => 'document.pdf']);

        $this->assertEquals([[
            'file' => '/path/to/file.pdf',
            'options' => ['as' => 'document.pdf'],
        ]], $message->attachments);
    }

    /**
     * Test toArray method
     *
     * @return void
     */
    public function testToArray(): void
    {
        $message = MailMessage::create()
            ->subject('Test Subject')
            ->greeting('Hello')
            ->line('Line 1')
            ->action('Click', 'https://example.com')
            ->line('Line 2')
            ->salutation('Bye');

        $array = $message->toArray();

        $this->assertEquals('info', $array['level']);
        $this->assertEquals('Test Subject', $array['subject']);
        $this->assertEquals('Hello', $array['greeting']);
        $this->assertEquals(['Line 1'], $array['introLines']);
        $this->assertEquals('Click', $array['actionText']);
        $this->assertEquals('https://example.com', $array['actionUrl']);
        $this->assertEquals(['Line 2'], $array['outroLines']);
        $this->assertEquals('Bye', $array['salutation']);
    }

    /**
     * Test method chaining
     *
     * @return void
     */
    public function testMethodChaining(): void
    {
        $message = MailMessage::create()
            ->subject('Test')
            ->greeting('Hello')
            ->line('Line 1')
            ->success()
            ->action('Click', 'url')
            ->from('from@example.com')
            ->cc('cc@example.com')
            ->attach('/file.pdf');

        $this->assertInstanceOf(MailMessage::class, $message);
        $this->assertEquals('success', $message->level);
    }
}
