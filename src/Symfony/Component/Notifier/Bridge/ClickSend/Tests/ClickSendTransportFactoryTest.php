<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Notifier\Bridge\ClickSend\Tests;

use Symfony\Component\Notifier\Bridge\ClickSend\ClickSendTransportFactory;
use Symfony\Component\Notifier\Test\TransportFactoryTestCase;

final class ClickSendTransportFactoryTest extends TransportFactoryTestCase
{
    public function createFactory(): ClickSendTransportFactory
    {
        return new ClickSendTransportFactory();
    }

    public function createProvider(): iterable
    {
        yield ['clicksend://host.test', 'clicksend://apiUsername:ApiKey@host.test'];
        yield ['clicksend://host.test?from=15556667777', 'clicksend://apiUsername:ApiKey@host.test?from=15556667777'];
        yield ['clicksend://host.test?source=api', 'clicksend://apiUsername:ApiKey@host.test?source=api'];
        yield ['clicksend://host.test?list_id=1', 'clicksend://apiUsername:ApiKey@host.test?list_id=1'];
        yield ['clicksend://host.test?from_email=foo%40bar.com', 'clicksend://apiUsername:ApiKey@host.test?from_email=foo%40bar.com'];
        yield ['clicksend://host.test?from=15556667777&source=api&list_id=1&from_email=foo%40bar.com', 'clicksend://apiUsername:ApiKey@host.test?from=15556667777&source=api&list_id=1&from_email=foo%40bar.com'];
    }

    public function incompleteDsnProvider(): iterable
    {
        yield 'missing API username and API key' => ['clicksend://@default'];
        yield 'missing API username or API key' => ['clicksend://apiUsername@default'];
    }

    public function supportsProvider(): iterable
    {
        yield [true, 'clicksend://apiUsername:apiKey@default'];
        yield [false, 'somethingElse://apiUsername:apiKey@default'];
    }

    public function unsupportedSchemeProvider(): iterable
    {
        yield ['somethingElse://apiUsername:apiKey@default'];
    }
}
