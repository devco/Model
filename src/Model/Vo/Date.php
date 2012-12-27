<?php

namespace Model\Vo;
use DateTime;
use DateTimeZone;

/**
 * Date / Time VO.
 * 
 * @category ValueObjects
 * @package  Model
 * @author   Trey Shugart <treshugart@gmail.com>
 * @license  Copyright (c) 2010 Trey Shugart http://europaphp.org/license
 */
class Date extends VoAbstract
{
    /**
     * The configuration for the date.
     * 
     * @var array
     */
    private $config = array(
        'format'   => 'Y-m-d\TH:i:s\Z',
        'timezone' => null
    );
    
    /**
     * The date.
     * 
     * @var DateTime
     */
    private $date;
    
    /**
     * Sets up a new date VO.
     * 
     * @param array $config The date configuration.
     * 
     * @return Date
     */
    public function __construct(array $config = array())
    {
        $this->config = array_merge($this->config, $config);
        
        if ($this->config['timezone']) {
            $this->date = new DateTime('now', new DateTimeZone($this->config['timezone']));
        } else {
            $this->date = new DateTime('now');
        }
    }
    
    /**
     * Sets the value.
     * 
     * @param mixed $value The value to set.
     * 
     * @return void
     */
    public function set($value)
    {
        if (is_numeric($value)) {
            $this->date = new DateTime(date($this->config['format'], $value));
        } elseif ($value) {

            if ($value instanceof DateTime) {
                $this->date = $value;
            } elseif ($this->date instanceof DateTime) {
                $this->date->modify($value);
            } else {
                $this->date = new DateTime($value);
            }

        } else {
            $this->date = null;
        }
    }
    
    /**
     * Returns the value.
     * 
     * @return mixed
     */
    public function get()
    {
        return $this->date ? $this->date->format($this->config['format']) : null;
    }
    
    /**
     * Returns whether or not the VO has a value.
     * 
     * @return bool
     */
    public function exists()
    {
        return isset($this->date);
    }
    
    /**
     * Initializes the value.
     * 
     * @return void
     */
    public function remove()
    {
        $this->date->modify('now');
    }
}
