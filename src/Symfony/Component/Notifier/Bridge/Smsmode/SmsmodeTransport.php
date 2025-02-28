<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\Smsmode;

use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author gnito-org <https://github.com/gnito-org>
 */
final class SmsmodeTransport extends AbstractTransport
{
    protected const HOST = 'rest.smsmode.com';

    public function __construct(
        #[\SensitiveParameter] private readonly string $apiKey,
        private readonly ?string $from = null,
        HttpClientInterface $client = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        $queryParameters = [];
        if ($this->from) {
            $queryParameters['from'] = $this->from;
        }

        return sprintf('smsmode://%s', $this->getEndpoint()).($queryParameters ? '?'.http_build_query($queryParameters) : null);
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage && (null === $message->getOptions() || $message->getOptions() instanceof SmsmodeOptions);
    }

    /**
     * https://dev.smsmode.com/sms/v1/message.
     */
    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        $endpoint = sprintf('https://%s/sms/v1/messages', $this->getEndpoint());

        $opts = $message->getOptions();
        $options = $opts ? $opts->toArray() : [];
        $options['body']['text'] = $message->getSubject();
        $options['recipient']['to'] = $message->getPhone();

        if (!isset($options['from'])) {
            $options['from'] = $this->from;
        }

        if (!preg_match('/^[a-zA-Z0-9\s]{1,11}$/', $options['from'] ?? '')) {
            throw new InvalidArgumentException(sprintf('The "From" value "%s" is not a valid sender ID.', $this->from));
        }

        if (isset($options['sent_date'])) {
            $options['sentDate'] = $options['sent_date'];
            unset($options['sent_date']);
        }

        if (isset($options['ref_client'])) {
            $options['refClient'] = $options['ref_client'];
            unset($options['ref_client']);
        }

        $response = $this->client->request('POST', $endpoint, [
                'headers' => ['X-Api-Key' => $this->apiKey],
                'json' => array_filter($options),
        ]);

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the remote Smsmode server.', $response, 0, $e);
        }

        if (201 !== $statusCode) {
            $error = $response->getContent(false);
            throw new TransportException(sprintf('Unable to send the SMS - "%s".', $error ?: 'unknown failure'), $response);
        }

        $success = $response->toArray(false);

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId($success['messageId']);

        return $sentMessage;
    }
}
