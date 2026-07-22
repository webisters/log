<?php
/*
 * This file is part of Webisters Log Library.
 *
 * (c) Hafiz Muhammad Moaz <thewebisters@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Log\Loggers;

use Framework\Log\Log;
use Framework\Log\Loggers\EmailLogger;
use Framework\Log\LogLevel;
use Tests\Log\TestCase;

final class EmailLoggerTest extends TestCase
{
    protected function setUp() : void
    {
        // error_log() with message type 1 needs a working MTA, which CI runners
        // do not have. Stub the delivery so the inherited Logger tests still
        // exercise the log handling, and keep makeHeaders() in the path.
        $this->logger = new class(destination: 'developer@localhost.localdomain') extends EmailLogger {
            protected function write(Log $log) : bool
            {
                $this->makeHeaders($log);
                return true;
            }
        };
    }

    public function testInvalidDestination() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email destination: foo.tld');
        new EmailLogger(destination: 'foo.tld');
    }

    public function testMakeHeaders() : void
    {
        $logger = new class('developer@localhost.localdomain') extends EmailLogger {
            public function setConfig(array $config) : static
            {
                return parent::setConfig($config);
            }

            public function makeHeaders(Log $log) : string
            {
                return parent::makeHeaders($log);
            }
        };
        $log = new Log(LogLevel::DEBUG, 'Foo', \time(), 'abc');
        self::assertSame('Subject: Log DEBUG abc', $logger->makeHeaders($log));
        $logger->setConfig([
            'headers' => [
                'subject' => 'Foo bar',
                'Foo' => 'Bar',
            ],
        ]);
        self::assertSame(
            "subject: Foo bar\r\nFoo: Bar",
            $logger->makeHeaders($log)
        );
    }
}
