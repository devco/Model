<?php

namespace Provider;
use Model\Entity\Entity;

class LogEntity extends Entity
{
    /**
     * @vo Model\Vo\Integer
     */
    public $id;

    /**
     * @vo Model\Vo\HasOne 'Provider\ContentEntity'
     */
    public $content;
}
