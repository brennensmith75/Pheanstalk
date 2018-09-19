<?php

namespace Pheanstalk\Command;

use Pheanstalk\Contract\ResponseInterface;

/**
 * The 'reserve' command.
 *
 * Reserves/locks a ready job in a watched tube.
 *
 * @author  Paul Annesley
 * @package Pheanstalk
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class ReserveCommand
    extends AbstractCommand
    implements \Pheanstalk\Contract\ResponseParserInterface
{
    private $_timeout;

    /**
     * A timeout value of 0 will cause the server to immediately return either a
     * response or TIMED_OUT.  A positive value of timeout will limit the amount of
     * time the client will block on the reserve request until a job becomes
     * available.
     *
     * @param int $timeout
     */
    public function __construct($timeout = null)
    {
        $this->_timeout = $timeout;
    }

    /* (non-phpdoc)
     * @see Command::getCommandLine()
     */
    public function getCommandLine()
    {
        return isset($this->_timeout) ?
            sprintf('reserve-with-timeout %s', $this->_timeout) :
            'reserve';
    }

    /* (non-phpdoc)
     * @see ResponseParser::parseResponse()
     */
    public function parseResponse($responseLine, $responseData)
    {
        if (in_array($responseLine, array(ResponseInterface::RESPONSE_DEADLINE_SOON, ResponseInterface::RESPONSE_TIMED_OUT), true)) {
            return $this->_createResponse($responseLine);
        }

        list($code, $id) = explode(' ', $responseLine);

        return $this->_createResponse($code, array(
            'id'      => (int) $id,
            'jobdata' => $responseData,
        ));
    }
}
