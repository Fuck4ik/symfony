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

use Symfony\Component\Notifier\Bridge\Smsmode\SmsmodeTransportFactory;
use Symfony\Component\Notifier\Test\TransportFactoryTestCase;

final class SmsmodeTransportFactoryTest extends TransportFactoryTestCase
{
    public function createFactory(): SmsmodeTransportFactory
    {
        return new SmsmodeTransportFactory();
    }

    public function createProvider(): iterable
    {
        yield ['smsmode://host.test?from=test', 'smsmode://ApiKey@host.test?from=test'];
    }

    public function incompleteDsnProvider(): iterable
    {
        yield 'missing API key' => ['smsmode://@default?from=test'];
    }

    public function missingRequiredOptionProvider(): iterable
    {
        yield 'missing option: from' => ['smsmode://apiKey@default'];
    }

    public function supportsProvider(): iterable
    {
        yield [true, 'smsmode://apiKey@default?from=test'];
        yield [false, 'somethingElse://apiKey@default?from=test'];
    }

    public function unsupportedSchemeProvider(): iterable
    {
        yield ['somethingElse://apiKey@default?from=test'];
    }
}
