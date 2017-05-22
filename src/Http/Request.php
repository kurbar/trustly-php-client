<?php

namespace Trustly\Http;

use Trustly\Exception\DataException;
use Trustly\Util\Util;

/**
 * Class Request
 * @package Trustly\Http
 * @author Karl Viiburg <karl@neocard.fi>
 */
class Request
{

    /**
     * @var array|null $payload Payload container
     */
    private $payload = null;

    /**
     * Request constructor. 
     *
     * @param string $method
     * @param array $data
     * @param array $attributes
     *
     * @throws DataException
     */
    public function __construct($method, $data = null, $attributes = null)
    {
        if (isset($data) || isset($attributes)) {
            $this->payload = array('params' => array());

            if (isset($data)) {
                if (!is_array($data) && isset($attributes)) {
                    throw new DataException("Data must be an array if attributes are provided");
                }

                $this->payload['params']['Data'] = $data;
            }

            if (isset($attributes)) {
                if (!isset($this->payload['params']['Data'])) {
                    $this->payload['params']['Data'] = array();
                }

                $this->payload['params']['Data']['Attributes'] = $attributes;
            }
        }

        $vacuumed = Util::vacuum($this->payload);
        if (isset($vacuumed)) {
            $this->payload = $vacuumed;
        }

        if (isset($method)) {
            $this->payload['method'] = $method;
        }

        if (!isset($this->payload['params'])) {
            $this->payload['params'] = array();
        }

        $this->set('version', '1.1');
    }

    /**
     * Get payload element.
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
     * Set payload element.
     * 
     * @param string $name
     * @param string $value
     */
    public function set($name, $value)
    {
        $this->payload[$name] = Util::ensureUTF8($value);
    }
    
    /**
     * Get UUID from payload.
     * 
     * @return null|string
     */
    public function getUUID()
    {
        if (isset($this->payload['params']['UUID'])) {
            return $this->payload['params']['UUID'];
        }

        return null;
    }
    
    /**
     * Set UUID to payload.
     * 
     * @param string $uuid
     */
    public function setUUID($uuid)
    {
        $this->payload['params']['UUID'] = Util::ensureUTF8($uuid);
    }
    
    /**
     * Set method to payload.
     * 
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->set('method', $method);
    }
    
    /**
     * Get method from payload.
     * 
     * @return array|mixed|null
     */
    public function getMethod()
    {
        return $this->get('method');
    }
    
    /**
     * Set data to payload
     * 
     * @param string $name
     * @param string $value
     *
     * @return mixed
     */
    public function setData($name, $value)
    {
        if (!isset($this->payload['params']['Data'])) {
            $this->payload['params']['Data'] = array();
        }

        $this->payload['params']['Data'][$name] = Util::ensureUTF8($value);
        return $value;
    }
    
    /**
     * Get param from payload.
     * 
     * @param string $name
     *
     * @return null|string
     */
    public function getParam($name)
    {
        if (isset($this->payload['params'][$name])) {
            return $this->payload['params'][$name];
        }

        return null;
    }
    
    /**
     * Set param to payload.
     * 
     * @param string $name
     * @param string $value
     */
    public function setParam($name, $value)
    {
        $this->payload['params'][$name] = Util::ensureUTF8($value);
    }
    
    /**
     * Get data from payload.
     * 
     * @param string $name
     *
     * @return string|null
     */
    public function getData($name = null)
    {
        if (isset($name)) {
            if (isset($this->payload['params']['Data'][$name])) {
                return $this->payload['params']['Data'][$name];
            }
        } else {
            if (isset($this->payload['params']['Data'])) {
                return $this->payload['params']['Data'];
            }
        }

        return null;
    }
    
    /**
     * Set attribute to payload.
     * 
     * @param string $name
     * @param string $value
     *
     * @return mixed
     */
    public function setAttribute($name, $value)
    {
        if (!isset($this->payload['params']['Data'])) {
            $this->payload['params']['Data'] = array();
        }

        if (!isset($this->payload['params']['Data']['Attributes'])) {
            $this->payload['params']['Data']['Attributes'] = array();
        }

        $this->payload['params']['Data']['Attributes'][$name] = Util::ensureUTF8($value);
        return $value;
    }
    
    /**
     * Get attribute from payload
     * 
     * @param string $name
     *
     * @return null|string
     */
    public function getAttribute($name)
    {
        if (isset($this->payload['params']['Data']['Attributes'][$name])) {
            return $this->payload['params']['Data']['Attributes'][$name];
        }

        return null;
    }
    
    /**
     * Encode payload to JSON.
     * 
     * @return string
     */
    public function toJson()
    {
        return Util::json($this->payload);
    }

}
