<?php

namespace Provider;

class LogRepository extends BaseRepository
{
    /**
     * @ensure Set of Provider\LogEntity
     */
    protected function getAll()
    {
        return [
            ['id' => 1, 'content' => ['id' => 1, 'name' => 'One']],
            ['id' => 2, 'content' => ['id' => 2, 'name' => 'Two']],
        ];
    }
}
