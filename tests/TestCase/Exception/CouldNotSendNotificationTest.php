<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Exception;

use Cake\TestSuite\TestCase;
use Crustum\Notification\Exception\CouldNotSendNotification;

/**
 * CouldNotSendNotification Exception Test
 */
class CouldNotSendNotificationTest extends TestCase
{
    /**
     * Test channelNotConfigured factory method
     *
     * @return void
     */
    public function testChannelNotConfigured(): void
    {
        $exception = CouldNotSendNotification::channelNotConfigured('telegram');

        $this->assertInstanceOf(CouldNotSendNotification::class, $exception);
        $this->assertStringContainsString('telegram', $exception->getMessage());
        $this->assertStringContainsString('not configured', $exception->getMessage());
        $this->assertEquals('telegram', $exception->getChannel());
    }

    /**
     * Test serviceRespondedWithError factory method
     *
     * @return void
     */
    public function testServiceRespondedWithError(): void
    {
        $response = ['error' => 'Invalid token'];
        $exception = CouldNotSendNotification::serviceRespondedWithError('slack', $response);

        $this->assertStringContainsString('slack', $exception->getMessage());
        $this->assertEquals('slack', $exception->getChannel());
        $this->assertEquals($response, $exception->getResponse());
    }

    /**
     * Test serviceRespondedWithError with custom message
     *
     * @return void
     */
    public function testServiceRespondedWithErrorCustomMessage(): void
    {
        $response = ['error' => 'Rate limit exceeded'];
        $exception = CouldNotSendNotification::serviceRespondedWithError(
            'twilio',
            $response,
            'Rate limit exceeded for Twilio',
        );

        $this->assertStringContainsString('Rate limit exceeded', $exception->getMessage());
        $this->assertEquals('twilio', $exception->getChannel());
    }

    /**
     * Test missingCredentials factory method
     *
     * @return void
     */
    public function testMissingCredentials(): void
    {
        $exception = CouldNotSendNotification::missingCredentials('telegram', 'bot_token');

        $this->assertStringContainsString('telegram', $exception->getMessage());
        $this->assertStringContainsString('bot_token', $exception->getMessage());
        $this->assertEquals('telegram', $exception->getChannel());
    }

    /**
     * Test missingRoutingInformation factory method
     *
     * @return void
     */
    public function testMissingRoutingInformation(): void
    {
        $exception = CouldNotSendNotification::missingRoutingInformation('slack');

        $this->assertStringContainsString('slack', $exception->getMessage());
        $this->assertStringContainsString('routing information', $exception->getMessage());
        $this->assertEquals('slack', $exception->getChannel());
    }
}
