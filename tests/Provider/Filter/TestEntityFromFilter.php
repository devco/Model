<?php

namespace Provider\Filter;

class TestEntityFromFilter
{
    public function __invoke($entity)
    {
        $entity['testOneNamespaceFrom'] = true;
        return $entity;
    }
}