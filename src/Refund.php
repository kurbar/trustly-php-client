<?php

namespace Trustly;

use Trustly\Contracts\Method as MethodContract;
use Trustly\Http\Request;

/**
 * Class Refund
 * @package Trustly
 * @author Karl Viiburg <karl@neocard.fi>
 */

/**
 * @property string $OrderID
 * @property string $Amount
 * @property string $Currency
 */
class Refund extends Api implements MethodContract
{

    /**
     * @var array $requiredData Required Data variables for request
     */
    private $requiredData = array('OrderID', 'Amount', 'Currency');

    /**
     * @var array $requiredAttributes Required Attribute variables for request
     */
    private $requiredAttributes = array();

    private $data = array();
    private $attributes = array();

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $request = new Request('Refund', $this->data, $this->attributes);
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
            throw new \InvalidArgumentException("Parameter $name is not defined for Refund request");
        }
    }

}
