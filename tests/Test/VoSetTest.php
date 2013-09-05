<?php

namespace Test;
use Model\Entity\Set;
use Provider\ContentEntity;
use Testes\Test\UnitAbstract;

/**
 * Tests the VoSet component.
 *
 * @category Sets
 * @package  Model
 * @author   Russell Holman <russholio@gmail.com>
 * @license  Copyright (c) 2013 Russell Holman http://europaphp.org/license
 */
class VoSetTest extends UnitAbstract
{
    private $entity;

    public function setUp()
    {
        $this->entity = new ContentEntity([
            'id'   => 1,
            'name' => 'test 1',
            'tags' => [
                'tag1',
                'tag2',
                'tag3',
                'tag4',
                '5tag'
            ]
        ]);
    }

    public function integrity()
    {
        $array = $this->entity->to();

        $this->assert($this->entity->tags instanceof \Model\Entity\VoSet, 'The Vo should be an instance of Entity\\VoSet');
        $this->assert(is_array($array['tags']) && count($array['tags']) === 5, 'Vo set should be returned as an array');

        $this->assert(count($this->entity->tags) === 5, 'The VoSet should be countable and return a count of 5, found '.count($this->entity->tags));

        $this->assert($this->entity->tags->append('6tag')->count() === 6, 'Adding the 6tag should add an extra element');
    }

    public function findingMany()
    {
        $found = $this->entity->tags->find([ 'tag2', '5tag' ]);

        $this->assert($found->count() === 2, "Wrong number of items found, expected 2, found '{$found->count()}'");
        $this->assert($found[0] === 'tag2', "The first item should tag2, found '{$found[0]}'");
        $this->assert($found[1] === '5tag', 'The second item should be 5tag.');
    }

    public function findingOne()
    {
        $found = $this->entity->tags->findOne([ 'tag4' ]);
        $this->assert($found === 'tag4', 'The value should be tag4');
    }
}