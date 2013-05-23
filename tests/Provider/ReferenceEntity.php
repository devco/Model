<?php

namespace Provider;
use Model\Entity\Entity;

class ReferenceEntity extends Entity
{
    /**
     * @vo Model\Vo\String
     */
    public $id;

    /**
     * @vo Model\Vo\String
     */
    public $contentId;

    /**
     * @vo Model\Vo\String
     */
    public $description;

    /**
     * @vo Model\Vo\String
     */
    public $link;
}