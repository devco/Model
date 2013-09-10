<?php

namespace Model\Vo;

class Integer extends VoAbstract
{
    public function init()
    {
        return $this->config['allowNull'] ? null : 0;
    }

    public function translate($value)
    {
        return is_null($value) && $this->config['allowNull'] ? null : (int) $value;
    }
}