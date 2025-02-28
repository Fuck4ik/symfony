<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\Smsmode\Tests;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Notifier\Bridge\Smsmode\SmsmodeOptions;
use Symfony\Component\Notifier\Bridge\Smsmode\SmsmodeTransport;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Test\TransportTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SmsmodeTransportTest extends TransportTestCase
{
    public function createTransport(HttpClientInterface $client = null, string $from = 'test_from'): SmsmodeTransport
    {
        return new SmsmodeTransport('test_api_key', $from, $client ?? $this->createMock(HttpClientInterface::class));
    }

    public function invalidFromProvider(): iterable
    {
        yield 'sender number too send' => ['aaaaaaaaaaaa'];
    }

    public function supportedMessagesProvider(): iterable
    {
        yield [new SmsMessage('0611223344', 'Hello!')];
        yield [new SmsMessage('0611223344', 'Hello!', 'from', new SmsmodeOptions(['ref_client' => 'test_ref_client']))];
    }

    /**
     * @dataProvider invalidFromProvider
     */
    public function testInvalidArgumentExceptionIsThrownIfFromIsInvalid(string $from)
    {
        $transport = $this->createTransport(null, $from);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The "From" value "%s" is not a valid sender ID.', $from));

        $transport->send(new SmsMessage('+33612345678', 'Hello!'));
    }

    /**
     * @dataProvider validFromProvider
     */
    public function testNoInvalidArgumentExceptionIsThrownIfFromIsValid(string $from)
    {
        $message = new SmsMessage('+33612345678', 'Hello!');

        $response = $this->createMock(ResponseInterface::class);

        $response->expects(self::exactly(2))->method('getStatusCode')->willReturn(201);

        $response->expects(self::once())->method('getContent')->willReturn(json_encode(['messageId' => 'foo']));

        $client = new MockHttpClient(function (string $method, string $url) use ($response): ResponseInterface {
            self::assertSame('POST', $method);
            self::assertSame('https://rest.smsmode.com/sms/v1/messages', $url);

            return $response;
        });

        $transport = $this->createTransport($client, $from);

        $sentMessage = $transport->send($message);

        self::assertSame('foo', $sentMessage->getMessageId());
    }

    public function toStringProvider(): iterable
    {
        yield ['smsmode://rest.smsmode.com?from=test_from', $this->createTransport()];
    }

    public function unsupportedMessagesProvider(): iterable
    {
        yield [new ChatMessage('Hello!')];
        yield [$this->createMock(MessageInterface::class)];
    }

    public function validFromProvider(): iterable
    {
        yield ['a'];
        yield ['ab'];
        yield ['abc'];
        yield ['abcd'];
        yield ['abcde'];
        yield ['abcdef'];
        yield ['abcdefg'];
        yield ['abcdefgh'];
        yield ['abcdefghi'];
        yield ['abcdefghij'];
        yield ['abcdefghijk'];
        yield ['abcdef ghij'];
        yield [' abcdefghij'];
        yield ['abcdefghij '];
    }
}
