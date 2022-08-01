<?php
namespace HrxApi;

use HrxApi\Helper;

class Receiver
{
    /* Class variables */
    private $name;
    private $email;
    private $phone;
    
    /**
     * Constructor
     * @since 1.0.0
     */
    public function __construct()
    {

    }

    /**
     * Set receiver name
     * @since 1.0.0
     *
     * @param (string) $name - Receiver name
     * @return (object) - Edited this class object
     */
    public function setName( $name )
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get receiver name
     * @since 1.0.0
     * 
     * @return (string) - Receiver name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set receiver email
     * @since 1.0.0
     *
     * @param (string) $email - Receiver email
     * @return (object) - Edited this class object
     */
    public function setEmail( $email )
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get receiver email
     * @since 1.0.0
     * 
     * @return (string) - Receiver email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set receiver phone
     * @since 1.0.0
     *
     * @param (string) $phone - Receiver phone
     * @param (string) $regex - Regex value by which the phone need check
     * @return (object) - Edited this class object
     */
    public function setPhone( $phone, $regex = '' )
    {
        if ( ! empty($regex) && ! Helper::checkRegex( $phone, $regex ) ) {
            $error_message = 'Bad phone number format';
            if ( substr($phone, 0, 1) == '+' ) {
                $error_message .= '. Phone number must be without code';
            }
            Helper::throwError($error_message);
        }

        $this->phone = $phone;

        return $this;
    }

    /**
     * Get receiver phone
     * @since 1.0.0
     * 
     * @return (string) - Receiver phone
     */
    public function getPhone()
    {
        return $this->phone;
    }
}
