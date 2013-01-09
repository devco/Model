<?php

namespace Provider;
use Model\Entity\Entity;

/**
 * @filter to noPassword using Model\Filter\Generic\RemovePassword.
 */
class PasswordEntity extends Entity
{
    /**
     * @vo Model\Vo\String
     */
    public $password;

}
