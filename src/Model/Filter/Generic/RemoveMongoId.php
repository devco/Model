<?php

namespace Model\Filter\Generic;

class RemoveMongoId
{
    const NAME = '_id';

    public function __invoke(array $data)
    {
        if (isset($data[self::NAME])) {
            unset($data[self::NAME]);
        }

        return $data;
    }
}
