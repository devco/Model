<?php

namespace Test\Vo;
use Model\Vo\Integer;
use Testes\Test\UnitAbstract;

class IntegerTest extends UnitAbstract
{
    public function integer()
    {
        $integer = new Integer;

        $this->assert($integer->init() === 0, 'Initialized value was not 0');

        $this->assert($integer->translate(null) === 0, 'Translated value was not 0');

        $this->assert($integer->translate('7') === 7, 'Translated value was not the integer 7');

        $this->assert($integer->translate(11) === 11, 'Translated value was not the integer 11');
    }

    public function nullableInteger()
    {
        $integer = new Integer([ 'allowNull' => true ]);

        $this->assert(is_null($integer->init()), 'Initialized value was not null');

        $this->assert(is_null($integer->translate(null)), 'Translated value was not null');

        $this->assert($integer->translate('7') === 7, 'Translated value was not the integer 7');
    }
}
