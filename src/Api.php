<?php

namespace Trustly;

use Trustly\Exception\ConnectionException;
use Trustly\Exception\DataException;
use Trustly\Exception\SignatureException;
use Trustly\Http\Request;
use Trustly\Http\Response;
use Trustly\Util\Util;

/**
 * Class Api
 * @package Trustly
 * @author Karl Viiburg <karl@neocard.fi>
 */
class Api
{

    /**
     * @var string $url Trustly API url
     */
    private $url;

    /**
     * @var string $username Trustly API username
     */
    private $username;

    /**
     * @var string $password Trustly API password
     */
    private $password;

    /**
     * @var string $rawMerchantKey Raw merchant key
     */
    private $rawMerchantKey;

    /**
     * @var bool|resource $merchantKey The merchants private key
     */
    private $merchantKey;

    /**
     * @var string $publicKey Trustly API public key
     */
    private $publicKey;

    /**
     * @var Request $lastRequest The last made request
     */
    private $lastRequest;

    /**
     * Api constructor.
     *
     * @param string $url
     * @param string $username
     * @param string $password
     * @param string $merchantKey
     * @param string $publicKey
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($url, $username, $password, $merchantKey, $publicKey)
    {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
        $this->rawMerchantKey = $merchantKey;
        $this->publicKey = $publicKey;

        $privateKey = Util::loadMerchantPrivateKey($merchantKey);
        if ($privateKey === false) {
            throw new \InvalidArgumentException("The merchant key could not be found at " . realpath(__DIR__ . '/' . $merchantKey));
        }

        $this->merchantKey = $privateKey;
    }

    /**
     * Make a call to the Trustly API.
     *
     * @param Request $request
     *
     * @return Response
     * @throws ConnectionException
     * @throws DataException
     * @throws SignatureException
     */
    protected function call(Request $request)
    {
        $uuid = $request->getUUID();
        if ($uuid === null) {
            $request->setUUID(Util::generateUUID());
        }

        if ($this->addCredentials($request) !== true) {
            throw new DataException("Unable to add authorization parameters to outgoing request");
        }

        $this->lastRequest = $request;

        $jsonPayload = $request->toJson();
        $result = $this->post($jsonPayload);

        $response = new Response($request, $result, Util::loadMerchantPublicKey($this->publicKey));

        if ($response->verifyResponse() === false) {
            throw new SignatureException("Incoming message signature is not valid", $response->getData());
        }

        if ($response->getUUID() !== $request->getUUID()) {
            throw new DataException("Incoming message is not related to request. UUID mismatch.");
        }

        return $response;
    }

    /**
     * Add Trustly API credentials to the Request
     *
     * @param Request $request
     *
     * @return bool
     * @throws SignatureException
     */
    protected function addCredentials(Request $request)
    {
        $request->setData('Username', $this->username);
        $request->setData('Password', $this->password);

        $signature = $this->sign($request);
        if ($signature === false) {
            return false;
        }

        $request->setParam('Signature', $signature);
        return true;
    }

    /**
     * Sign the request with the merchants private key.
     *
     * @param Request $request
     *
     * @return string
     * @throws SignatureException
     */
    protected function sign(Request $request)
    {
        if (!isset($this->merchantKey)) {
            throw new \InvalidArgumentException("No private key has been loaded for signing");
        }

        $method = $request->getMethod();
        if ($method === null) {
            $method = '';
        }

        $uuid = $request->getUUID();
        if ($uuid === null) {
            $uuid = '';
        }

        $data = $request->getData();

        $serialData = $method . $uuid . Util::serialize($data);
        $rawSignature = '';

        Util::clearOpenSSLError();
        if (openssl_sign($serialData, $rawSignature, $this->merchantKey, OPENSSL_ALGO_SHA1) === true) {
            return base64_encode($rawSignature);
        }

        throw new SignatureException("Failed to sign the outgoing merchant request. " . openssl_error_string());
    }

    /**
     * Create a cURL POST request to Trustly API.
     *
     * @param string $payload
     *
     * @return array
     * @throws ConnectionException
     */
    private function post($payload = null)
    {
        $ch = curl_init();

        /**
         * cURL options
         */

        // Generic
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));

        // Configuration
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // SSL
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        // POST data
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        if (isset($payload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        
        syslog(LOG_DEBUG, $this->url . " -> " . $payload);

        $body = curl_exec($ch);

        if ($body === false) {
            $error = curl_error($ch);
            if ($error === null) {
                $error = 'Failed to connect to the Trustly API';
            }

            throw new ConnectionException($error);
        }

        $sslResult = curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT);
        if ($sslResult !== 0) {
            $sslErrorString = null;
            if (isset(Util::$curlX509Errors[$sslResult])) {
                $sslErrorString = Util::$curlX509Errors[$sslResult];
            }

            $error = "Failed to connect to the Trustly API. SSL verification error #$sslResult" . ($sslResult ? ": $sslErrorString" : "");
            throw new ConnectionException($error);
        }

        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $response = array(
            'http_code' => intval($responseCode),
            'body' => $body
        );
        
        syslog(LOG_DEBUG, json_encode($response) . ' <- HTTP ' . $responseCode . ' ' . $this->url);

        return $response;
    }

}
