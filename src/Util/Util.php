<?php

namespace Trustly\Util;

/**
 * Class Util
 * @package Trustly\Util
 * @author Karl Viiburg <karl@neocard.fi>
 */
class Util
{

    /**
     * @var array $curlX509Errors All possible cURL X509 errors
     */
    public static $curlX509Errors = array(
        '0' => 'X509_V_OK',
        '2' => 'X509_V_ERR_UNABLE_TO_GET_ISSUER_CERT',
        '3' => 'X509_V_ERR_UNABLE_TO_GET_CRL',
        '4' => 'X509_V_ERR_UNABLE_TO_DECRYPT_CERT_SIGNATURE',
        '5' => 'X509_V_ERR_UNABLE_TO_DECRYPT_CRL_SIGNATURE',
        '6' => 'X509_V_ERR_UNABLE_TO_DECODE_ISSUER_PUBLIC_KEY',
        '7' => 'X509_V_ERR_CERT_SIGNATURE_FAILURE',
        '8' => 'X509_V_ERR_CRL_SIGNATURE_FAILURE',
        '9' => 'X509_V_ERR_CERT_NOT_YET_VALID',
        '10' => 'X509_V_ERR_CERT_HAS_EXPIRED',
        '11' => 'X509_V_ERR_CRL_NOT_YET_VALID',
        '12' => 'X509_V_ERR_CRL_HAS_EXPIRED',
        '13' => 'X509_V_ERR_ERROR_IN_CERT_NOT_BEFORE_FIELD',
        '14' => 'X509_V_ERR_ERROR_IN_CERT_NOT_AFTER_FIELD',
        '15' => 'X509_V_ERR_ERROR_IN_CRL_LAST_UPDATE_FIELD',
        '16' => 'X509_V_ERR_ERROR_IN_CRL_NEXT_UPDATE_FIELD',
        '17' => 'X509_V_ERR_OUT_OF_MEM',
        '18' => 'X509_V_ERR_DEPTH_ZERO_SELF_SIGNED_CERT',
        '19' => 'X509_V_ERR_SELF_SIGNED_CERT_IN_CHAIN',
        '20' => 'X509_V_ERR_UNABLE_TO_GET_ISSUER_CERT_LOCALLY',
        '21' => 'X509_V_ERR_UNABLE_TO_VERIFY_LEAF_SIGNATURE',
        '22' => 'X509_V_ERR_CERT_CHAIN_TOO_LONG',
        '23' => 'X509_V_ERR_CERT_REVOKED',
        '24' => 'X509_V_ERR_INVALID_CA',
        '25' => 'X509_V_ERR_PATH_LENGTH_EXCEEDED',
        '26' => 'X509_V_ERR_INVALID_PURPOSE',
        '27' => 'X509_V_ERR_CERT_UNTRUSTED',
        '28' => 'X509_V_ERR_CERT_REJECTED',
        '29' => 'X509_V_ERR_SUBJECT_ISSUER_MISMATCH',
        '30' => 'X509_V_ERR_AKID_SKID_MISMATCH',
        '31' => 'X509_V_ERR_AKID_ISSUER_SERIAL_MISMATCH',
        '32' => 'X509_V_ERR_KEYUSAGE_NO_CERTSIGN',
        '50' => 'X509_V_ERR_APPLICATION_VERIFICATION'
    );

    /**
     * Load the merchants public key from the .pem file.
     *
     * @param string $merchantKey
     *
     * @return bool|resource
     */
    public static function loadMerchantPublicKey($merchantKey)
    {
        $keyFile = realpath(__DIR__ . '/' . $merchantKey);

        if (!file_exists($keyFile)) {
            return false;
        }

        $cert = file_get_contents($keyFile);
        return openssl_pkey_get_public($cert);
    }

    /**
     * Load the merchants private key from the .pem file.
     *
     * @param $merchantKey
     *
     * @return bool|resource
     */
    public static function loadMerchantPrivateKey($merchantKey)
    {
        $keyFile = realpath(__DIR__ . '/' . $merchantKey);

        if (!file_exists($keyFile)) {
            return false;
        }

        $cert = file_get_contents($keyFile);
        return openssl_pkey_get_private($cert);
    }

    /**
     * Serialize the data for Trustly submission.
     *
     * @param $data
     *
     * @return string
     */
    public static function serialize($data)
    {
        if (is_array($data)) {
            ksort($data);
            $serializedData = '';

            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    $serializedData .= self::serialize($value);
                } else {
                    $serializedData .= $key . self::serialize($value);
                }
            }

            return $serializedData;
        } else {
            return (string) $data;
        }
    }

    /**
     * Generate a new UUID.
     *
     * @return string
     */
    public static function generateUUID()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Ensures the string is indeed UTF-8 encoded.
     *
     * @param $str
     *
     * @return null|string
     */
    public static function ensureUTF8($str)
    {
        if ($str == null) {
            return null;
        }

        $enc = mb_detect_encoding($str, array('ISO-8859-1', 'ISO-8859-15', 'UTF-8', 'ASCII'));
        if ($enc !== false) {
            if ($enc == 'ISO-8859-1' || $enc == 'ISO-8859-15') {
                $str = mb_convert_encoding($str, 'UTF-8', $enc);
            }
        }

        return $str;
    }

    /**
     * Encode the data to a JSON string.
     *
     * @param array $data
     * @param bool $pretty
     *
     * @return string
     */
    public static function json($data, $pretty = false)
    {
        if ($pretty) {
            $sorted = $data;
            self::sortRecursive($sorted);
            return json_encode($sorted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode($data);
        }
    }
    
    /**
     * Vacuum the supplied data and remove unset values.
     * 
     * @param array $data
     *
     * @return array|null
     */
    public static function vacuum($data)
    {
        if (is_null($data)) {
            return null;
        }

        if (is_array($data)) {
            $ret = array();

            foreach ($data as $k => $v) {
                $nv = self::vacuum($v);
                if (isset($nv)) {
                    $ret[$k] = $nv;
                }
            }

            if (count($ret)) {
                return $ret;
            }

            return null;
        } else {
            return $data;
        }
    }

    /**
     * Clear any hanging OpenSSL errors in memory.
     *
     * @return void
     */
    public static function clearOpenSSLError()
    {
        while ($err = openssl_error_string());
    }

    /**
     * Sort an array recursively.
     *
     * @param array $data
     * 
     * @return void
     */
    private function sortRecursive($data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $this->sortRecursive($v);
                    $data[$k] = $v;
                }
            }
            ksort($data);
        }
    }

}
