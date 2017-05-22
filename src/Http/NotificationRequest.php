<?php

namespace Trustly\Http;

use Trustly\Exception\DataException;
use Trustly\Exception\JSONRPCVersionException;
use Trustly\Util\Util;

/**
 * Class NotificationRequest
 * @package Trustly\Http
 * @author Karl Viiburg <karl@neocard.fi>
 */
class NotificationRequest
{
    
    /**
     * @var array $payload The payload container
     */
    private $payload;
    
    /**
     * NotificationRequest constructor.
     *
     * @param string $body
     * 
     * @throws DataException
     * @throws JSONRPCVersionException
     */
    public function __construct($body)
    {
        if (empty($body)) {
            throw new DataException("Empty notification body");
        }

        $payload = json_decode($body, true);

        if ($payload == null) {
            throw new DataException("Failed to parse JSON: " . json_last_error_msg());
        }

        $this->payload = $payload;

        if ($this->getVersion() != '1.1') {
            throw new JSONRPCVersionException("JSON RPC Version '" . $this->getVersion() . "' is not supported");
        }
    }
    
    /**
     * Get params array or element from payload.
     * 
     * @param string $name
     *
     * @return mixed|null
     */
    public function getParams($name = null)
    {
        if (!isset($this->payload['params'])) {
            return null;
        }

        if (isset ($name)) {
            if (isset($this->payload['params'][$name])) {
                return $this->payload['params'][$name];
            }
        } else {
            return $this->payload['params'];
        }

        return null;
    }
    
    /**
     * Get data array or element from payload.
     * 
     * @param string $name
     *
     * @return mixed|null
     */
    public function getData($name = null)
    {
        if (!isset($this->payload['params']['data'])) {
            return null;
        }

        if (isset ($name)) {
            if (isset($this->payload['params']['data'][$name])) {
                return $this->payload['params']['data'][$name];
            }
        } else {
            return $this->payload['params']['data'];
        }

        return null;
    }
    
    /**
     * Get UUID from request payload.
     * 
     * @return string
     */
    public function getUUID()
    {
        return $this->getParams('uuid');
    }
    
    /**
     * Get method from request payload.
     * 
     * @return array|mixed|null
     */
    public function getMethod()
    {
        return $this->get('method');
    }
    
    /**
     * Get signature from request payload.
     * 
     * @return string
     */
    public function getSignature()
    {
        return $this->getParams('signature');
    }
    
    /**
     * Get signature from request payload.
     * 
     * @return array|mixed|null
     */
    public function getVersion()
    {
        return $this->get('version');
    }
    
    /**
     * Get entire payload or a payload element.
     * 
     * @param string $name
     *
     * @return array|mixed|null
     */
    public function get($name = null)
    {
        if ($name == null) {
            return $this->payload;
        } else {
            if (isset ($this->payload[$name])) {
                return $this->payload[$name];
            }
        }

        return null;
    }
    
    /**
     * Encode the payload to a json string.
     * 
     * @return string
     */
    public function json()
    {
        return Util::json($this->payload);
    }

}
