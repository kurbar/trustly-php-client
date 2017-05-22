<?php

namespace Trustly\Contracts;

use Trustly\Http\Response;
use Trustly\Exception\DataException;
use Trustly\Exception\SignatureException;

/**
 * Interface Method
 * @package Trustly\Contracts
 * @author Karl Viiburg <karl@neocard.fi>
 */
interface Method
{
    
    /**
     * @return Response
     * @throws DataException
     * @throws SignatureException
     */
    public function commit();
    
    /**
     * Set data or attribute variable.
     *
     * @param $name
     * @param $value
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function __set($name, $value);
    
}
