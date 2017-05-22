<?php

namespace Trustly;

use Trustly\Exception\SignatureException;
use Trustly\Http\NotificationRequest;
use Trustly\Http\NotificationResponse;
use Trustly\Util\Util;

/**
 * Class Notification
 * @package Trustly
 * @author Karl Viiburg <karl@neocard.fi>
 */
class Notification
{

    /**
     * This is a method that Trustly will call when the end-user has completed the payment process
     * but before the money has been received by Trustly.
     */
    const METHOD_PENDING = 'pending';

    /**
     * This is a method that Trustly will call when the end-user's account balance should be credited
     * (increased). This could for example be when a deposit has been made or a previously requested
     * withdrawal has been cancelled.
     */
    const METHOD_CREDIT = 'credit';

    /**
     * This is a method that Trustly will call when the end-user's account balance should be debited
     * (decreased). This could for example be if a previous deposit is disputed.
     */
    const METHOD_DEBIT = 'debit';

    /**
     * This is a method that Trustly will call when the order had been cancelled by the end-user.
     */
    const METHOD_CANCEL = 'cancel';

    /**
     * @var \DB_common $db The Pear DB handler.
     */
    private $db = null;

    /**
     * @var NotificationRequest $request The incoming notification request from Trustly
     */
    private $request;

    /**
     * @var bool|resource $privateKey The merchants private key
     */
    private $privateKey;

    /**
     * @var bool|resource $publicKey Trustly API public key
     */
    private $publicKey;

    /**
     * Notification constructor.
     *
     * @param NotificationRequest $request
     * @param string $merchantKey
     * @param string $publicKey
     *
     * @throws SignatureException
     */

    public function __construct(NotificationRequest $request, $merchantKey, $publicKey)
    {
        if ($this->db == null) {
            require_once(__DIR__ . '/../../db.php');
            $this->db = getDB();
        }

        $this->request = $request;

        $this->privateKey = Util::loadMerchantPrivateKey($merchantKey);
        $this->publicKey = Util::loadMerchantPublicKey($publicKey);

        if ($this->verifyNotification() === false) {
            $this->saveSignatureStatus($this->request->getData('enduserid'),$this->request->getData('orderid'), intval($this->request->getData('notificationid')), 1);
            throw new SignatureException("Incoming message signature is not valid", $this->request->getData());
        } else{$this->saveSignatureStatus($this->request->getData('enduserid'),$this->request->getData('orderid'),intval($this->request->getData('notificationid')),0);}
    }


    /**
     * The given notification is a 'pending' notification.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->request->getMethod() == self::METHOD_PENDING;
    }

    /**
     * The given notification is a 'credit' notification.
     *
     * @return bool
     */
    public function isCredit()
    {
        return $this->request->getMethod() == self::METHOD_CREDIT;
    }

    /**
     * The given notification is a 'debit' notification.
     *
     * @return bool
     */
    public function isDebit()
    {
        return $this->request->getMethod() == self::METHOD_DEBIT;
    }

    /**
     * The given notification is a 'cancel' notification.
     *
     * @return bool
     */
    public function isCancel()
    {
        return $this->request->getMethod() == self::METHOD_CANCEL;
    }

    /**
     * Check if the notification request is a duplicate.
     *
     * @return bool
     */
    public function isDuplicate()
    {
        $notificationId = intval($this->request->getData('notificationid'));

        $sql = "SELECT COUNT(id) FROM trustly_notification WHERE id = $notificationId";
        $res = $this->db->getOne($sql);
        if (\DB::isError($res)) {
            // TODO: How to handle? Assume false for now
            syslog(LOG_WARNING, __NAMESPACE__ . " Notification isDuplicate() SQL Error: " . $res->getUserInfo());
            return false;
        }

        return intval($res) > 0;
    }

    /**
     * Save the notification to the database.
     *
     * @return bool
     */
    public function save()
    {
        $notificationId = intval($this->request->getData('notificationid'));
        $uuid = $this->request->getUUID();
        $method = $this->request->getMethod();
        $signature = $this->request->getSignature();
        $data = $this->request->json();

        $sql = "INSERT INTO trustly_notification (id, uuid, method, signature, data, created_at) VALUES ($notificationId, '$uuid', '$method', '$signature', '$data', NOW())";
        $res = $this->db->query($sql);
        if (\DB::isError($res)) {
            syslog(LOG_WARNING, __NAMESPACE__ . " Notification save() SQL Error: " . $res->getUserInfo());
            return false;
        }

        return true;
    }

    /**
     * Verify the notification request.
     *
     * @return bool
     */
    public function verifyNotification()
    {
        $method = $this->request->getMethod();
        $uuid = $this->request->getUUID();
        $signature = $this->request->getSignature();
        $data = $this->request->getData();

        return $this->verifySignedData($method, $uuid, $signature, $data);
    }

    /**
     * Send the response to Trustly.
     *
     * @param bool $success
     *
     * @return bool|NotificationResponse
     * @throws SignatureException
     */
    public function sendResponse($success = false)
    {
        $response = new NotificationResponse($this->request, $success);

        $signature = $this->sign($response);
        if ($signature === false) {
            return false;
        }

        $response->setSignature($signature);
        return $response;
    }

    /**
     * Verify notification request signed data.
     *
     * @param string $method
     * @param string $uuid
     * @param string $signature
     * @param array $data
     *
     * @return bool
     */
    private function verifySignedData($method, $uuid, $signature, $data)
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
     * Sign the notification response going back to Trustly.
     *
     * @param NotificationResponse $response
     *
     * @return string
     * @throws SignatureException
     */
    protected function sign(NotificationResponse $response)
    {
        if (!isset($this->privateKey)) {
            throw new \InvalidArgumentException("No private key has been loaded for signing");
        }

        $method = $response->getMethod();
        if ($method === null) {
            $method = '';
        }

        $uuid = $response->getUUID();
        if ($uuid === null) {
            $uuid = '';
        }

        $data = $response->getData();

        $serialData = $method . $uuid . Util::serialize($data);
        $rawSignature = '';

        Util::clearOpenSSLError();
        if (openssl_sign($serialData, $rawSignature, $this->privateKey, OPENSSL_ALGO_SHA1) === true) {
            return base64_encode($rawSignature);
        }

        throw new SignatureException("Failed to sign the outgoing merchant request. " . openssl_error_string());
    }

    public function saveSignatureStatus($customerId, $uuid, $notifyId, $isbadSign)
    {
        $sql = "INSERT INTO trustly_signature_load (customer_id, order_id, is_bad_signature, created, notification_id) VALUES ('$customerId','$uuid', '$isbadSign', now() , {$notifyId})";
        $res = $this->db->query($sql);
        if (\DB::isError($res)) {
            syslog(LOG_WARNING, __NAMESPACE__ . " SignatureStatus save() SQL Error: " . $res->getUserInfo());
            return false;
        }

        return true;
    }
    
}
