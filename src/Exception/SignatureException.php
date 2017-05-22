<?php

namespace Trustly\Exception;

/**
 * Class SignatureException
 * @package Trustly\Exception
 * 
 * Thrown whenever we encounter a response or notification request from the API that is signed
 * with an incorrect signature. This is serious and could be an indication that message
 * contents are being tampered with.
 */
class SignatureException extends \Exception
{
    
    /**
     * @var array $signatureData
     */
    private $signatureData;
    
    /**
     * SignatureException constructor.
     *
     * @param string $message Exception message
     * @param array  $data Data that was signed with an invalid signature
     */
    public function __construct($message, $data = null)
    {
        parent::__construct($message);
        $this->signatureData = $data;
    }
    
    /**
     * Get the data that had an invalid signature. This is the only way to get data from
     * anything with a bad signature. This should be used for DEBUGGING ONLY. You
     * should NEVER rely on the contents.
     * 
     * @return array
     */
    public function getBadData()
    {
        return $this->signatureData;
    }
    
}
