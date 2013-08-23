<?php

namespace Model\Vo;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;

class VoSet extends VoAbstract
{
    private $class;

    public function __construct($class, array $args = [])
    {
        $this->class = (new ReflectionClass($class))->newInstanceArgs($args);
    }

    public function translate($value)
    {
        $arr = [];

        if (is_array($value) || is_object($value)) {
            foreach ($value as $k => $v) {
                $arr[$k] = $this->class->translate($v);
            }
        }

        return $arr;
    }
}
