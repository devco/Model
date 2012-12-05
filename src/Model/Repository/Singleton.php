<?php

namespace Model\Repository;
use ReflectionClass;

/**
 * Allows a repository to behave like a singleton.
 * 
 * @category Repositories
 * @package  Model
 * @author   Trey Shugart <treshugart@gmail.com>
 * @license  Copyright (c) 2011 Trey Shugart http://europaphp.org/license
 */
trait Singleton
{
    /**
     * The repository instance.
     * 
     * @var Singleton
     */
    private static $instances = [];
    
    /**
     * Instantiates the repository and returns the response from the called method.
     * 
     * @param string $name The method name.
     * @param array  $args The method args.
     * 
     * @return mixed
     */
    static public function __callStatic($name, array $args = [])
    {
        $self = get_called_class();
        
        // ensure an instance is available
        if (!isset(self::$instances[$self])) {
            self::init();
        }
        
        // allow proxying through __call
        if (method_exists(self::$instances[$self], '__call')) {
            return self::$instances[$self]->__call($name, $args);
        }
        
        // ensure the method is protected
        if (!(new ReflectionMethod($class, $name))->isProtected()) {
            throw new LogicException(sprintf(
                'In order to enable static calling of "%s->%s()", you must mark it as protected.',
                get_called_class(),
                $name
            ));
        }
        
        // simply call the method
        return call_user_func_array([self::$instances[$self], $name], $args);
    }
    
    /**
     * Allows the instance to be initialised using arguments before it is automatically instantiated when a method is
     * invoked via __callStatic().
     * 
     * @return void
     */
    static public function init()
    {
        $self = get_called_class();
        
        if (func_num_args()) {
            self::$instances[$self] = (new ReflectionClass(get_called_class()))->newInstanceArgs(func_get_args());
        } else {
            self::$instances[$self] = new static;
        }
    }
}
