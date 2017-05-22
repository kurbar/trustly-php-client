<?php

namespace Trustly\Http;

use Trustly\Exception\ConnectionException;
use Trustly\Exception\DataException;
use Trustly\Exception\JSONRPCVersionException;
use Trustly\Util\Util;

/**
 * Class Response
 * @package Trustly\Http
 * @author Karl Viiburg <karl@neocard.fi>
 */
class Response
{
    
    /**
     * @var string $responseBody cURL response body
     */
    private $responseBody;
    
    /**
     * @var int $responseCode cURL HTTP response code
     */
    private $responseCode;
    
    /**
     * @var string $publicKey Trustly API public key
     */
    private $publicKey;
    
    /**
     * @var string $result Final payload result (either result or error)
     */
    private $result;
    
    /**
     * @var mixed $payload The response payload
     */
    private $payload;
    
    /**
     * Response constructor.
     *
     * @param Request $request
     * @param array $result
     * @param string $publicKey
     * 
     * @throws ConnectionException
     * @throws DataException
     * @throws JSONRPCVersionException
     */
    public function __construct(Request $request, $result, $publicKey)
    {
        $this->responseBody = $result['body'];
        $this->responseCode = $result['http_code'];
        $this->publicKey = $publicKey;

        $payload = json_decode($result['body'], true);
        if ($payload === false) {
            if (isset($this->responseCode) && $this->responseCode != 200) {
                throw new ConnectionException("HTTP {$this->responseCode}");
            } else {
                throw new DataException("Failed to decode JSON response. Reason: " . json_last_error());
            }
        }

        if (isset($payload)) {
            $this->payload = $payload;
        }

        if (isset($this->payload['result'])) {
            $this->result = $this->payload['result'];
        } else if (isset($this->payload['error'])) {
            $this->result = $this->payload['error'];
        } else {
            throw new DataException("No 'result' or 'error' in response");
        }

        $version = $this->get('version');
        if ($version !== '1.1') {
            throw new JSONRPCVersionException("JSON RPC Version $version is not supported. Version 1.1 is required. " . json_encode($this->payload));
        }
    }
    
    /**
     * Verify the received response.
     * 
     * @return bool
     */
    public function verifyResponse()
    {
        $method = $this->getMethod();
        $uuid = $this->getUUID();
        $signature = $this->getSignature();
        $data = $this->getData();

        return $this->verifySignedData($method, $uuid, $signature, $data);
    }
    
    /**
     * Verify the responses data signature.
     * 
     * @param string $method
     * @param string $uuid
     * @param string $signature
     * @param array $data
     *
     * @return bool
     */
    protected function verifySignedData($method, $uuid, $signature, $data)
    {
        if ($method === null) {
            $method = '';
        }

        if ($uuid === null) {
            $uuid = '';
        }

        if (!isset($signature)) {
            return false;
        }

        $serialData = $method . $uuid . Util::serialize($data);
        $rawSignature = base64_decode($signature);

        return (boolean) openssl_verify($serialData, $rawSignature, $this->publicKey, OPENSSL_ALGO_SHA1);
    }
    
    /**
     * Check if the response is an error response.
     * 
     * @return bool
     */
    public function isError()
    {
        return $this->get('error') !== null;
    }
    
    /**
     * Check if the response is a result response.
     * 
     * @return bool
     */
    public function isSuccess()
    {
        return $this->get('result') !== null;
    }
    
    /**
     * Get error message from error response.
     * 
     * @return string|null
     */
    public function getErrorMessage()
    {
        if ($this->isError() && isset($this->result['message'])) {
            return $this->result['message'];
        }

        return null;
    }
    
    /**
     * Get error code from error response.
     * 
     * @return string|null
     */
    public function getErrorCode()
    {
        if ($this->isError() && isset($this->result['code'])) {
            return $this->result['code'];
        }

        return null;
    }
    
    /**
     * Get the result/error payload.
     * 
     * @param string $name
     *
     * @return mixed|null|string
     */
    public function getResult($name = null)
    {
        if ($name === null) {
            return $this->result;
        }

        if (is_array($this->result) && isset($this->result[$name])) {
            return $this->result[$name];
        }

        return null;
    }
    
    /**
     * Get the UUID from response payload.
     * 
     * @return null|string
     */
    public function getUUID()
    {
        if (isset($this->result['uuid'])) {
            return $this->result['uuid'];
        }

        return null;
    }
    
    /**
     * Get the method from response payload.
     * 
     * @return null|string
     */
    public function getMethod()
    {
        if (isset($this->result['method'])) {
            return $this->result['method'];
        }

        return null;
    }
    
    /**
     * Get the signature from response payload.
     * 
     * @return null|string
     */
    public function getSignature()
    {
        if (isset($this->result['signature'])) {
            return $this->result['signature'];
        }

        return null;
    }
    
    /**
     * Get the data from response payload.
     * 
     * @param string $name
     *
     * @return mixed|null|string
     */
    public function getData($name = null)
    {
        $data = null;

        if (isset($this->result['data'])) {
            $data = $this->result['data'];
        } else {
            return null;
        }

        if (isset($name)) {
            if (isset($data[$name])) {
                return $data[$name];
            } else {
                return null;
            }
        } else {
            return $data;
        }
    }
    
    /**
     * Get payload element.
     * 
     * @param string $name
     *
     * @return mixed|null|string
     */
    private function get($name = null)
    {
        if ($name === null) {
            return $this->payload;
        } else {
            if (isset($this->payload[$name])) {
                return $this->payload[$name];
            }
        }

        return null;
    }

}
