<?php

namespace Provider\Filter;

class ContentEntityFilterToString
{
    public function __invoke($entity)
    {
        return [
            'name' => $entity['name']
        ];
    }
}