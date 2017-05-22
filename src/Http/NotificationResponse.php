<?php

namespace Trustly\Http;

use Trustly\Util\Util;

/**
 * Class NotificationResponse
 * @package Trustly\Http
 * @author Karl Viiburg <karl@neocard.fi>
 */
class NotificationResponse
{
    
    /**
     * @var array $payload The payload container
     */
    private $payload = array();
    
    /**
     * NotificationResponse constructor.
     *
     * @param NotificationRequest $request
     * @param bool $success
     */
    public function __construct(NotificationRequest $request, $success = null)
    {
        $uuid = $request->getUUID();
        $method = $request->getMethod();

        if (isset($uuid)) {
            $this->setResult('uuid', $uuid);
        }

        if (isset($method)) {
            $this->setResult('method', $method);
        }

        if (isset($success)) {
            $this->setSuccess($success);
        }

        $this->set('version', '1.1');
    }
    
    /**
     * Set the status data.
     * 
     * @param bool $success
     */
    public function setSuccess($success = null)
    {
        $status = 'OK';

        if (isset($success) && $success !== true) {
            $status = 'FAILED';
        }

        $this->setData('status', $status);
    }
    
    /**
     * Set the signature.
     * 
     * @param string $signature
     */
    public function setSignature($signature)
    {
        $this->setResult('signature', $signature);
    }
    
    /**
     * Set the payloads result.
     * 
     * @param string $name
     * @param string $value
     *
     * @return void
     */
    public function setResult($name, $value)
    {
        if (!isset($this->payload['result'])) {
            $this->payload['result'] = array();
        }

        $this->payload['result'][$name] = $value;
    }
    
    /**
     * Get the result array or element from the payload.
     * 
     * @param string $name
     *
     * @return mixed|null
     */
    public function getResult($name = null)
    {
        $result = null;

        if (isset($this->payload['result'])) {
            $result = $this->payload['result'];
        } else {
            return null;
        }

        if (isset($name)) {
            if (isset($result[$name])) {
                return $result[$name];
            }
        } else {
            return $result;
        }

        return null;
    }
    
    /**
     * Get the data array or element from the payload
     * 
     * @param string $name
     *
     * @return mixed|null
     */
    public function getData($name = null)
    {
        $data = null;

        if (isset($this->payload['result']['data'])) {
            $data = $this->payload['result']['data'];
        } else {
            return null;
        }

        if (isset($name)) {
            if (isset($data[$name])) {
                return $data[$name];
            }
        } else {
            return $data;
        }

        return null;
    }
    
    /**
     * Set new data element in the payload.
     * 
     * @param string $name
     * @param string $value
     * 
     * @return void
     */
    public function setData($name, $value)
    {
        if (!isset($this->payload['result'])) {
            $this->payload['result'] = array();
        }
        if (!isset($this->payload['result']['data'])) {
            $this->payload['result']['data'] = array($name => $value);
        } else {
            $this->payload['result']['data'][$name] = $value;
        }
    }
    
    /**
     * Get the method from payload.
     * 
     * @return string
     */
    public function getMethod()
    {
        return $this->getResult('method');
    }
    
    /**
     * Get the UUID from payload.
     * 
     * @return string
     */
    public function getUUID()
    {
        return $this->getResult('uuid');
    }
    
    /**
     * Set a payload element.
     * 
     * @param string $name
     * @param string $value
     */
    public function set($name, $value)
    {
        $this->payload[$name] = Util::ensureUTF8($value);
    }
    
    /**
     * Encode the payload to a JSON string.
     * 
     * @return string
     */
    public function json()
    {
        return Util::json($this->payload);
    }

}
