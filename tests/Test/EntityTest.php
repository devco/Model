<?php

namespace Test;
use Exception;
use Model\Entity\Set;
use Model\Validator\ValidatorException;
use Provider\CommentEntity;
use Provider\ContentEntity;
use Provider\MongoEntity;
use Provider\PasswordEntity;
use Provider\UserEntity;
use Testes\Test\UnitAbstract;

class EntityTest extends UnitAbstract
{
    public function constructorImporting()
    {
        $entity = new ContentEntity([
            'id'   => 1,
            'name' => 'test'
        ]);
        
        $this->assert($entity->id && $entity->name, 'The id or name was not set.');
    }

    public function relationships()
    {
        $entity = new ContentEntity;
        $this->assert($entity->user instanceof UserEntity, 'User relationship was not instantiated.');
        $this->assert($entity->comments instanceof Set, 'Comments relationship was not instantiated.');
        
        try {
            $entity->comments->offsetSet(0, new CommentEntity);
        } catch (Exception $e) {
            $this->assert(false, 'Entity could not be added to set.');
        }
    }

    public function testMappedGetters()
    {
        $user    = new UserEntity;
        $content = $user->getContent();
        
        $this->assert(count($content) === 2, 'There must be 2 content items returned.');
        $this->assert($content instanceof Set, 'The content items must be an entity set.');
        $this->assert($user->isLastAdministrator === true, 'The user must be the last administrator.');
    }

    public function mapping()
    {
        $content = new ContentEntity;
        $mapped  = $content->toArray('testMapper');

        $this->assert(array_key_exists('testName', $mapped), 'The mapper was not invoked.');
    }

    public function filtering()
    {
        $content = new MongoEntity([
            '_id' => 1
        ]);
        
        $this->assert($content->_id === '1', 'The mongo _id was never set');

        $data = $content->to('mongoId');

        $this->assert(!isset($data['_id']), 'The mongo _id is still set');

        $content = new PasswordEntity([
            'password' => 'pa55word'
        ]);

        $this->assert($content->password === 'pa55word', 'The password was never set');

        $data = $content->to('noPassword');

        $this->assert(!isset($data['password']), 'The password is still set');
    }

    public function validation()
    {
        $content     = new ContentEntity;
        $content->id = 1;

        try {
            $content->assert();
            $this->assert(false, 'Name validator should return false.');
        } catch (ValidatorException $e) {
            $this->assert($e[0] === 'Testing 1.', 'Correct error message was not returned.');
        }

        $this->assert(ContentEntity::$validatedUsingClass, 'The class validator was not invoked.');
        $this->assert(ContentEntity::$validatedUsingMethod, 'The method validator was not invoked.');
    }

    public function autoloading()
    {
        $content = new ContentEntity;

        $this->assert(count($content->references) === 2, 'Expected a set of 2 ReferenceEntities to be autoloaded');

        $content = new ContentEntity(['references' => []]);

        $this->assert(count($content->references) === 0, 'Expected an empty set of ReferenceEntities to be returned');
    }
}
