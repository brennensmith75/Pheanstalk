<?php

namespace Pheanstalk;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the Connection.
 * Relies on a running beanstalkd server.
 *
 * @author  Paul Annesley
 * @package Pheanstalk
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class ConnectionTest extends TestCase
{
    const CONNECT_TIMEOUT = 2;

    /**
     * @expectedException \Pheanstalk\Exception\ConnectionException
     */
    public function testConnectionFailsToIncorrectPort()
    {
        $connection = new Connection(
            SERVER_HOST,
            SERVER_PORT + 1
        );

        $command = new Command\UseCommand('test');
        $connection->dispatchCommand($command);
    }

    public function testDispatchCommandSuccessful()
    {
        $connection = new Connection(
            SERVER_HOST,
            SERVER_PORT
        );

        $command = new Command\UseCommand('test');
        $response = $connection->dispatchCommand($command);

        $this->assertInstanceOf(Contract\ResponseInterface::class, $response);
    }

    public function testPersistentConnection()
    {
        $timeout = null;
        $persistent = true;

        $connection = new Connection(
            SERVER_HOST,
            SERVER_PORT,
            $timeout,
            $persistent
        );

        $command = new Command\UseCommand('test');
        $response = $connection->dispatchCommand($command);

        $this->assertInstanceOf(Contract\ResponseInterface::class, $response);
    }

    public function testConnectionResetIfSocketExceptionIsThrown()
    {
        $pheanstalk = new Pheanstalk(
            SERVER_HOST,
            SERVER_PORT,
            self::CONNECT_TIMEOUT
        );

        $connection = $this->getMockBuilder('\Pheanstalk\Connection')
                     ->disableOriginalConstructor()
                     ->getMock();

        $connection->expects($this->any())
             ->method('getHost')
             ->will($this->returnValue(SERVER_HOST));
        $connection->expects($this->any())
             ->method('getPort')
             ->will($this->returnValue(SERVER_PORT));
        $connection->expects($this->any())
             ->method('getConnectTimeout')
             ->will($this->returnValue(self::CONNECT_TIMEOUT));

        $pheanstalk->useTube('testconnectionreset');
        $pheanstalk->put(__METHOD__);
        $pheanstalk->watchOnly('testconnectionreset');

        $pheanstalk->setConnection($connection);
        $connection->expects($this->once())
             ->method('dispatchCommand')
             ->will($this->throwException(new Exception\SocketException('socket error simulated')));
        $job = $pheanstalk->reserve();

        $this->assertEquals(__METHOD__, $job->getData());
    }

    public function testDisconnect()
    {
        $connection = $this->_getConnection();

        // initial connection
        $connection->dispatchCommand(new Command\StatsCommand());
        $this->assertTrue($connection->hasSocket());

        // disconnect
        $connection->disconnect();
        $this->assertFalse($connection->hasSocket());

        // auto-reconnect
        $connection->dispatchCommand(new Command\StatsCommand());
        $this->assertTrue($connection->hasSocket());
    }

    // ----------------------------------------
    // private

    private function _getConnection()
    {
        return new Connection(SERVER_HOST, SERVER_PORT);
    }
}
