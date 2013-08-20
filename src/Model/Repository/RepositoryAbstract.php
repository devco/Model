<?php

namespace Model\Repository;
use InvalidArgumentException;
use LogicException;
use Model\Cache\CacheInterface;
use Model\Configurator\DocComment\Repository\Configurator;
use Model\Entity\Entity;
use Model\Entity\Set;
use ReflectionClass;
use ReflectionMethod;

abstract class RepositoryAbstract
{
    const DEFAULT_NAME = 'default';

    private $cacheDrivers = [];

    private $cacheLifetimes = [];

    private $cacheLinks = [];

    private $joins = [];

    private $returnValueFilters = [];

    private static $instances = [];

    public function __construct()
    {
        if (method_exists($this, 'init')) {
            if (func_num_args()) {
                call_user_func_array([$this, 'init'], func_get_args());
            } else {
                $this->init();
            }
        }

        $this->configure();
    }

    public function __call($name, array $args = [])
    {
        return $this->callArgs($name, $args);
    }

    public function call($name)
    {
        $args = func_get_args();
        array_shift($args);
        return $this->callArgs($name, $args);
    }

    public function callArgs($name, array $args)
    {
        $this->throwIfMethodNotExists($name);

        if ($this->hasCache($name, $args)) {
            return $this->getCache($name, $args);
        }

        $value = call_user_func_array([$this, $name], $args);
        $value = $this->filterReturnValue($name, $value);

        $this->processJoins($name, $value);
        $this->setCache($name, $args, $value);

        return $value;
    }

    public function configure()
    {
        $conf = new Configurator;
        $conf->__invoke($this);
    }

    public function addJoin($method, $call, $field)
    {
        if (!isset($this->joins[$method])) {
            $this->joins[$method] = [];
        }

        $this->joins = [
            'call'  => $call,
            'field' => $field
        ];

        return $this;
    }

    public function setCacheDriver($name, CacheInterface $driver)
    {
        $this->cacheDrivers[$name] = $driver;
        return $this;
    }

    public function getCacheDriver($name)
    {
        if (!isset($this->cacheDrivers[$name])) {
            throw new InvalidArgumentException(sprintf(
                'The cache driver "%s" does not exist for repository "%s".',
                $name,
                get_class($this)
            ));
        }

        return $this->cacheDrivers[$name];
    }

    public function hasCacheDriver($name)
    {
        return isset($this->cacheDrivers[$name]);
    }

    public function removecacheDriver($name)
    {
        if (isset($this->cacheDrivers[$name])) {
            unset($this->cacheDrivers[$name]);
        }

        return $this;
    }

    public function setCacheDriverFor($method, $name, $lifetime = null)
    {
        if (!isset($this->cacheDrivers[$name])) {
            throw new InvalidArgumentException(sprintf(
                'The cannot apply cache to "%s" because repsoitory "%s" does not have a cache driver labelled "%s".',
                $method,
                get_class($this),
                $name
            ));
        }

        $this->methodCacheDrivers[$method]   = $this->cacheDrivers[$name];
        $this->methodCacheLifetimes[$method] = $lifetime;

        return $this;
    }

    public function getCacheDriverFor($method)
    {
        if (!isset($this->methodCacheDrivers[$method])) {
            throw new InvalidArgumentException(sprintf(
                'No cache driver exists for method "%s" in repository "%s".',
                $method,
                get_class($this)
            ));
        }

        return $this->methodCacheDrivers[$method];
    }

    public function removeCacheDriverFor($method)
    {
        if (isset($this->methodCacheDrivers[$method])) {
            unset($this->methodCacheDrivers[$method]);
        }

        return $this;
    }

    public function hasCacheDriverFor($method)
    {
        return isset($this->methodCacheDrivers[$method]);
    }

    public function setCache($method, array $args, $value)
    {
        if ($this->hasCacheDriverFor($method)) {
            return $this->getCacheDriverFor($method)->set(
                $this->generateCacheKey($method, $args),
                $value,
                isset($this->methodCacheLifetimes[$method]) ? $this->methodCacheLifetimes[$method] : null
            );
        }

        return $this;
    }

    public function getCache($method, array $args)
    {
        if ($this->hasCacheDriverFor($method)) {
            return $this->getCacheDriverFor($method)->get($this->generateCacheKey($method, $args));
        }
    }

    public function hasCache($method, array $args)
    {
        if ($this->hasCacheDriverFor($method)) {
            return $this->getCacheDriverFor($method)->has($this->generateCacheKey($method, $args));
        }

        return false;
    }

    public function removeCache($method, array $args)
    {
        if ($this->hasCacheDriverFor($method)) {
            $this->getCacheDriverFor($method)->remove($this->generateCacheKey($method, $args));
        }

        return $this;
    }

    public function clearCache()
    {
        foreach ($this->methodCacheDrivers as $method => $driver) {
            $this->getCacheDriverFor($method)->clear();
        }

        return $this;
    }

    public function setReturnValueFilter($method, callable $filter)
    {
        $this->returnValueFilters[$method] = $filter;
        return $this;
    }

    private function generateCacheKey($method, array $args)
    {
        return md5(get_class($this) . $method . serialize($args));
    }

    private function filterReturnValue($method, $value)
    {
        if (isset($this->returnValueFilters[$method])) {
            $value = $this->returnValueFilters[$method]($value);
        }

        return $value;
    }

    private function processJoins($method, $entityOrSet)
    {
        if (!isset($this->joins[$method])) {
            return;
        }

        if ($entityOrSet instanceof Set) {
            foreach ($entityOrSet as $entity) {
                $this->processJoins($method, $entity);
            }
        } elseif ($entityOrSet instanceof Entity) {
            foreach ($this->joins[$method] as $join) {
                $entityOrSet->__set($join['field'], $this->call($join['call'], $entityOrSet));
            }
        }
    }

    private function throwIfMethodNotExists($method)
    {
        if (!method_exists($this, $method)) {
            $class = get_class($this);
            $trace = debug_backtrace();

            foreach ($trace as $k => $call) {
                if ($call['class'] === $class && $call['function'] === $method) {
                    $origin = $trace[$k + 1];
                    $origin['file'] = $call['file'];
                    $origin['line'] = $call['line'];
                    break;
                }
            }

            throw new LogicException(sprintf(
                'The method "%s" does not exist in "%s" as called from "%s%s%s() in "%s" on line "%s".',
                $method,
                $class,
                $origin['class'],
                $origin['type'],
                $origin['function'],
                $origin['file'],
                $origin['line']
            ));
        }
    }

    static public function __callStatic($name, array $args = [])
    {
        $self = self::getInstance();

        if (method_exists($self, '__call')) {
            return $self->__call($name, $args);
        }

        return call_user_func_array([$self, $name], $args);
    }

    static public function getInstance($name = self::DEFAULT_NAME, array $args = [])
    {
        if (is_array($name)) {
            $args = $name;
            $name = self::DEFAULT_NAME;
        }
        
        $self = get_called_class() . $name;

        if (isset(self::$instances[$self]) && !$args) {
            return self::$instances[$self];
        }
        
        return self::initInstance($name, $args);
    }

    static public function initInstance($name = self::DEFAULT_NAME, array $args = [])
    {
        if (is_array($name)) {
            $args = $name;
            $name = self::DEFAULT_NAME;
        }

        $class = get_called_class();
        $self  = $class . $name;

        if ($args) {
            self::$instances[$self] = (new ReflectionClass($class))->newInstanceArgs($args);
        } else {
            self::$instances[$self] = new static;
        }

        return self::$instances[$self];
    }

    static public function removeInstance($name = self::DEFAULT_NAME)
    {
        $self = get_called_class() . $name;

        if (isset(self::$instances[$self])) {
            unset(self::$instances[$self]);
        }
    }
}