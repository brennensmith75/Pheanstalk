<?php

namespace Pheanstalk;

use Pheanstalk\Contract\CommandInterface;
use Pheanstalk\Contract\YamlResponseParserInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests exceptions thrown by ResponseParser implementations.
 *
 * @author  Paul Annesley
 * @package Pheanstalk
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class ResponseParserExceptionTest extends TestCase
{
    public function testDeleteNotFound()
    {
        $this->_expectServerExceptionForResponse(
            new Command\DeleteCommand($this->_mockJob(5)),
            'NOT_FOUND'
        );
    }

    public function testReleaseBuried()
    {
        $this->_expectServerExceptionForResponse(
            new Command\ReleaseCommand($this->_mockJob(5), 1, 0),
            'BURIED'
        );
    }

    public function testReleaseNotFound()
    {
        $this->_expectServerExceptionForResponse(
            new Command\ReleaseCommand($this->_mockJob(5), 1, 0),
            'NOT_FOUND'
        );
    }

    public function testBuryNotFound()
    {
        $this->_expectServerExceptionForResponse(
            new Command\BuryCommand($this->_mockJob(5), 1),
            'NOT_FOUND'
        );
    }

    public function testIgnoreNotIgnored()
    {
        $this->_expectServerExceptionForResponse(
            new Command\IgnoreCommand('test'),
            'NOT_IGNORED'
        );
    }

    public function testTouchNotFound()
    {
        $this->_expectServerExceptionForResponse(
            new Command\TouchCommand($this->_mockJob(5)),
            'NOT_FOUND'
        );
    }

    public function testPeekNotFound()
    {
        $this->_expectServerExceptionForResponse(
            new Command\PeekCommand(5),
            'NOT_FOUND'
        );
    }

    /**
     * @expectedException \Pheanstalk\Exception\CommandException
     */
    public function testPeekInvalidSubject()
    {
        new Command\PeekCommand('invalid');
    }

    public function testYamlResponseParserNotFound()
    {
        $this->_expectServerExceptionForResponse(
            new YamlResponseParserInterface(YamlResponseParserInterface::MODE_DICT),
            'NOT_FOUND'
        );
    }

    public function testPauseTubeNotFound()
    {
        $this->_expectServerExceptionForResponse(
            new Command\PauseTubeCommand('not-a-tube', 1),
            'NOT_FOUND'
        );
    }

    public function testPutUnhandledResponse()
    {
        $this->_expectExceptionForResponse(
            new Command\PutCommand('data', 0, 0, 0),
            'unhandled response'
        );
    }

    // ----------------------------------------

    /**
     * @param int $id
     */
    private function _mockJob($id)
    {
        $job = $this->getMockBuilder('\Pheanstalk\Job')
            ->disableOriginalConstructor()
            ->getMock();
        $job->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        return $job;
    }

    /**
     * @param CommandInterface
     * @param string the response line to parse.
     * @param string the type of exception to expect.
     */
    private function _expectExceptionForResponse($command, $response, $type = Exception::class)
    {
        $this->expectException($type);
        $command->parseResponse($response, null);
    }

    /**
     * @param CommandInterface
     * @param string the response line to parse.
     */
    private function _expectServerExceptionForResponse($command, $response)
    {
        $this->_expectExceptionForResponse($command, $response,
            '\Pheanstalk\Exception\ServerException');
    }
}
