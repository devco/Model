<?php

namespace Provider;
use Model\Entity\Entity;

/**
 * @mapper testMapper Provider\ContentMapper
 * 
 * @validator Provider\ContentValidator Test error message.
 * @validator contentValidator          Test error message.
 */
class ContentEntity extends Entity
{
    /**
     * @vo Model\Vo\String
     */
    public $id;
    
    /**
     * @vo Model\Vo\String
     * 
     * @validator validateNameExists Testing :id.
     */
    public $name;
    
    /**
     * @vo Model\Vo\HasOne 'Provider\UserEntity'
     */
    public $user;
    
    /**
     * @vo Model\Vo\HasMany 'Provider\CommentEntity'
     */
    public $comments;

    /**
     * @vo Model\Vo\HasMany 'Provider\ReferenceEntity'
     *
     * @autoload joinReferences
     */
    public $references;

    /**
     * @vo Model\Vo\VoSet 'Model\Vo\String'
     */
    public $tags;

    public static $validatedUsingClass = false;

    public static $validatedUsingMethod = false;

    public function contentValidator(self $content)
    {
        self::$validatedUsingMethod = true;
    }

    public function validateNameExists($name)
    {
        return $name ?: false;
    }

    protected function joinReferences() {
        return ReferenceRepository::getByContentId($this->id);
    }
}