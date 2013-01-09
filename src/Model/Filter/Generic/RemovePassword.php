<?php

namespace Model\Filter\Generic;

class RemovePassword
{
    const NAME = 'password';

    public function __invoke(array $data)
    {
        if (isset($data[self::NAME])) {
            unset($data[self::NAME]);
        }

        return $data;
    }
}
