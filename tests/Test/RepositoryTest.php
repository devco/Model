<?php

namespace Test;
use Provider\ContentEntity;
use Provider\ContentRepository;
use Provider\UserEntity;
use Provider\UserRepository;
use Testes\Test\UnitAbstract;

/**
 * Tests the Repository component.
 * 
 * @category Repositories
 * @package  Model
 * @author   Trey Shugart <treshugart@gmail.com>
 * @license  Copyright (c) 2011 Trey Shugart http://europaphp.org/license
 */
class RepositoryTest extends UnitAbstract
{
    /**
     * Ensures that the proper insert method is called.
     * 
     * @return void
     */
    public function inserting()
    {
        $repo   = new ContentRepository;
        $entity = new ContentEntity(array(
            'name' => 'Trey Shugart'
        ));
        
        // save once and test if it has an id
        $repo->save($entity);
        $this->assert($repo->findById($entity->id) instanceof ContentEntity, 'Id was not returned by insert method.');
    }
    
    /**
     * Ensures that the proper update method is called.
     * 
     * @return void
     */
    public function updating()
    {
        $repo   = new ContentRepository;
        $entity = new ContentEntity(array(
            'name' => 'my content'
        ));
        
        // save it for the first time
        $repo->save($entity);
        
        // modify it to see if it saves
        $entity->wasSaved = true;
        
        // save it again and test to see if the "wasSaved" property was saved
        $repo->save($entity);
        
        $this->assert($repo->findById($entity->id)->name === 'my content', 'The entity was not updated.');
    }
    
    /**
     * Ensures that the proper remove method is called.
     * 
     * @return void
     */
    public function removing()
    {
        $repo   = new ContentRepository;
        $entity = new ContentEntity(array('name' => 'test'));
        
        $repo->save($entity);
        if (!$repo->findById($entity->id)) {
            $this->assert(false, 'Cannot remove if item cannot be saved.');
        }
        
        $repo->remove($entity);
        if ($repo->findById(1)) {
            $this->assert(false, 'Item was not removed');
        }
    }
    
    /**
     * Ensures that caching can be automatically handled by using cache methods inside of repository methods.
     * 
     * @return void
     */
    public function caching()
    {
        $repo = new ContentRepository;
        $item = new ContentEntity;
        $repo->save($item);
        
        $item = $repo->findById($item->id);
        if (!$item) {
            $this->assert(false, 'Item should have been found.');
        }
        
        $item = $repo->findById($item->id);
        if ($repo->findByIdCallCount > 1) {
            $this->assert(false, 'Method "ContentRepository->findById()" was called more than once so the cache did not find the item.');
        }
    }

    public function repositoryInit()
    {
        $argument = 'test';
        MyTestRepository::init($argument);
        $this->assert(MyTestRepository::$argument == $argument, 'The argument was not initialised');
    }
}
