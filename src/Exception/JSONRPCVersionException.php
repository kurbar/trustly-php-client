<?php

namespace Trustly\Exception;

/**
 * Class JSONRPCVersionException
 * @package Trustly\Exception
 * 
 * Thrown if we encounter a response or notification request from teh API with
 * a JSON RPC version this API has not been built to handle.
 */
class JSONRPCVersionException extends \Exception
{
    
}
