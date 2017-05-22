<?php

namespace Trustly;

use Trustly\Contracts\Method as MethodContract;
use Trustly\Http\Request;

/**
 * Class Deposit
 * @package Trustly
 * @author Karl Viiburg <karl@neocard.fi>
 */

/**
 * @property string $NotificationURL
 * @property int $EndUserID
 * @property string $MessageID
 * @property string $Currency
 * @property string $Firstname
 * @property string $Lastname
 * @property string $Email
 * @property string $Locale
 * @property string $Country
 * @property string $Amount
 * @property string $IP
 * @property string $SuccessURL
 * @property string $FailURL
 * @property int $HoldNotifications
 */
class Deposit extends Api implements MethodContract
{

    /**
     * @var array $requiredData Required Data variables for request
     */
    private $requiredData = array('NotificationURL', 'EndUserID', 'MessageID');

    /**
     * @var array $requiredAttributes Required Attribute variables for request
     */
    private $requiredAttributes = array('Currency', 'Firstname', 'Lastname', 'Email', 'Locale', 'Country', 'Amount', 'IP', 'SuccessURL', 'FailURL', 'HoldNotifications');

    private $data = array();
    private $attributes = array();
    
    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $request = new Request('Deposit', $this->data, $this->attributes);
        return parent::call($request);
    }
    
    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        if (in_array($name, $this->requiredData)) {
            $this->data[$name] = $value;
        } else if (in_array($name, $this->requiredAttributes)) {
            $this->attributes[$name] = $value;
        } else {
            throw new \InvalidArgumentException("Parameter $name is not defined for Deposit request");
        }
    }

}
