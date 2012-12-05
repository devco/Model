<?php

namespace Test;
use Model\Repository\RepositoryAbstract;

class MyTestRepository extends RepositoryAbstract
{
    public static $argument;

    public function __construct($argument)
    {
        self::$argument = $argument;
    }
}

