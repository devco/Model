<?php

namespace Provider;
use Model\Entity\Entity;

/**
 * @filter to mongoId using Model\Filter\Generic\RemoveMongoId.
 */
class MongoEntity extends Entity
{
    /**
     * @vo Model\Vo\String
     */
    public $_id;

}
