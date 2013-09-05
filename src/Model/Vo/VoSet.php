<?php

namespace Model\Vo;
use ReflectionClass;
use Model\Entity;

class VoSet extends VoAbstract
{
    private $args;

    private $class;

    public function __construct($class, array $args = [])
    {
        $this->args  = $args;
        $this->class = $class;
    }

    public function init()
    {
        return new Entity\VoSet($this->class, $this->args);
    }

    public function translate($value)
    {
        return new Entity\VoSet($this->class, $this->args, $value);
    }

    public function from($value, $filter = null)
    {

        return (new Entity\VoSet($this->class, $this->args))->from($value, $filter);
    }

    public function to($value, $filter = null)
    {
        return $value->to($filter);
    }
}
