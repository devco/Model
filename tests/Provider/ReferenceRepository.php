<?php

namespace Provider;
use Model\Entity\Set;

class ReferenceRepository extends BaseRepository
{
    protected function getByContentId($id)
    {
        return new Set('Provider\ReferenceEntity', [[
            'id'          => 1,
            'contentId'   => $id,
            'description' => 'Wikipedia reference 1',
            'link'        => 'http://en.wikipedia.org/wiki/Domain-driven_design',

        ], [
            'id'          => 2,
            'contentId'   => $id,
            'description' => 'Clean Code published by Prentice Hall',
            'link'        => 'http://www.amazon.com/Clean-Code-Handbook-Software-Craftsmanship/dp/0132350882',
        ]]);
    }
}