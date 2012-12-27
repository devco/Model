<?php

namespace Test;
use Exception;
use Model\Vo\Date;
use Testes\Test\UnitAbstract;

/**
 * Tests the Date Entity.
 * 
 * @category Entities
 * @package  Model
 * @author   Otto Odgaard <oodgaard@ultraserve.com.au>
 * @license  Copyright (c) 2011 Trey Shugart http://europaphp.org/license
 */
class DateEntityTest extends UnitAbstract
{
    /**
     * Date stamp to use in tests.
     * 
     * @var int
     */
    private $time = 1262268000;

    /**
     * The default date format.
     * 
     * @var string
     */
    private $defaultFormat = 'Y-m-d\TH:i:s\Z';

    /**
     * Check the date is set from an integer.
     * 
     * @return void
     */
    public function setDateByInteger()
    {
        $date = new Date(['format' => $this->defaultFormat]);
        $date->set($this->time);
        $this->assert($date->get() == '2010-01-01T00:00:00Z', 'The date was not set from an integer');
    }

    /**
     * Check the date is set from a date string.
     *
     * @return void
     */
    public function setDateByString()
    {
        $date = new Date(['format' => $this->defaultFormat]);
        $date->set(date('Y-m-d H:i:s', $this->time));
        $this->assert($date->get() == '2010-01-01T00:00:00Z', 'The date was not set from a string');
    }

    /**
     * Check that the format parameter is used when getting the date.
     *
     * @return void
     */
    public function formatDate()
    {
        $date = new Date(['format' => 'Y-m-d\TH:i:s']);
        $date->set($this->time);
        $this->assert($date->get() == '2010-01-01T00:00:00', 'The date format was not used');
    }
}
