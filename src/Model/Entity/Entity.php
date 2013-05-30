<?php

namespace Model\Entity;
use InvalidArgumentException;
use Model\Configurator\DocComment\Entity\Configurator as EntityConfigurator;
use Model\Configurator\DocComment\Vo\Configurator as VoConfigurator;
use Model\Filter\Filterable;
use Model\Filter\FilterableInterface;
use Model\Mapper\MapperInterface;
use Model\Validator\Assertable;
use Model\Validator\AssertableInterface;
use Model\Vo\VoInterface;

class Entity implements AccessibleInterface, AssertableInterface
{
    use Assertable;

    use Filterable;

    private $autoloaded = [];

    private $autoloaders = [];

    private $data = [];

    private $mappers = [];

    private $vos = [];

    private static $cache = [];

    private static $cacheProperties = [
        'autoloaders',
        'exportFilters',
        'importFilters',
        'mappers',
        'validatorMessages',
        'validators',
        'vos'
    ];

    public static $serializeProperties = [
        'autoloaders',
        'data',
        'exportFilters',
        'importFilters',
        'mappers',
        'validatorMessages',
        'validators',
        'vos'
    ];

    public function __construct($data = [], $filterToUse = null)
    {
        $this->configure();
        $this->init();
        $this->from($data, $filterToUse);
    }

    public function __set($name, $value)
    {
        if (isset($this->vos[$name])) {
            $this->data[$name] = $this->vos[$name]->translate($value);

            if (isset($this->autoloaders[$name])) {
                $this->autoloaded[$name] = true;
            }
        }
    }

    public function __get($name)
    {
        if (isset($this->vos[$name])) {
            if (isset($this->autoloaders[$name]) && !isset($this->autoloaded[$name])) {
                $this->data[$name] = $this->vos[$name]->translate($this->{$this->autoloaders[$name]}());
                $this->autoloaded[$name] = true;
            }

            return $this->data[$name];
        }
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }
    }

    public function init()
    {

    }

    public function clear()
    {
        foreach ($this->vos as $name => $vo) {
            $vo->remove($this, $name);
        }

        return $this;
    }

    public function setVo($name, VoInterface $vo)
    {
        $this->vos[$name]  = $vo;
        $this->data[$name] = $vo->init();

        return $this;
    }

    public function getVo($name)
    {
        if (isset($this->vos[$name])) {
            return $this->vos[$name];
        }

        throw new InvalidArgumentException(sprintf(
            'The VO "%s" does not exist on the entity "%s".',
            $name,
            get_class($this)
        ));
    }

    public function hasVo($name)
    {
        return isset($this->vos[$name]);
    }

    public function removeVo($name)
    {
        if (isset($this->vos[$name])) {
            unset($this->data[$name]);
            unset($this->vos[$name]);
        }

        return $this;
    }

    /**
     * @deprecated
     */
    public function setMapper($name, MapperInterface $mapper)
    {
        $this->mappers[$name] = $mapper;
        return $this;
    }

    /**
     * @deprecated
     */
    public function getMapper($name)
    {
        if (!isset($this->mappers[$name])) {
            throw new InvalidArgumentException(sprintf('The mapper "%s" does not exist.', $name));
        }
        return $this->mappers[$name];
    }

    /**
     * @deprecated
     */
    public function hasMapper($name)
    {
        return isset($this->mappers[$name]);
    }

    /**
     * @deprecated
     */
    public function removeMapper($name)
    {
        if (isset($this->mappers[$name])) {
            unset($this->mappers[$name]);
        }

        return $this;
    }

    public function setAutoloader($name, $method)
    {
        if (!method_exists($this, $method)) {
            throw new InvalidArgumentException(sprintf(
                'The autoload method "%s" does not exist on "%s".',
                $method,
                get_class($this)
            ));
        }

        $this->autoloaders[$name] = $method;

        return $this;
    }

    public function getAutoloader($name)
    {
        if (!isset($this->autoloaders[$name])) {
            throw new InvalidArgumentException(sprintf('The autoloader "%s" does not exist.', $name));
        }

        return $this->autoloaders[$name];
    }

    public function hasAutoloader($name)
    {
        return isset($this->autoloaders[$name]);
    }

    public function isAutoloaded($name)
    {
        return isset($this->autoloaded[$name]);
    }

    public function removeAutoloader($name)
    {
        if (isset($this->autoloaders[$name])) {
            unset($this->autoloaders[$name]);
        }

        return $this;
    }

    public function autoload($name)
    {
        if (!$this->hasAutoloader($name)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot autoload "%s" for entity "%s" because it does not exist.',
                $name,
                get_class()
            ));
        }

        $this->__set($name, $this->{$this->getAutoloader($name)}());

        return $this;
    }

    public function autoloadAll()
    {
        foreach ($this->vos as $name => $vo) {
            if ($this->hasAutoloader($name) && !$this->isAutoloaded($name)) {
                $this->autoload($name);
            }
        }

        return $this;
    }

    public function from($data, $filterToUse = null)
    {
        if (!$data) {
            return $this;
        }

        foreach ($this->getImportFilters()->offsetGet($filterToUse) as $filter) {
            $data = $filter($data);
        }

        $data = $this->makeArrayFromAnything($data);

        // @deprected and can be removed once the mapper functionality is removed.
        if ($filterToUse && isset($this->mappers[$filterToUse])) {
            $data = $this->mappers[$filterToUse]->map($data);
        }

        foreach ($data as $name => $value) {
            if (isset($this->vos[$name])) {
                $this->data[$name] = $this->vos[$name]->translate($this->vos[$name]->from($value, $filterToUse));
            }

            if (isset($this->autoloaders[$name])) {
                $this->autoloaded[$name] = true;
            }
        }

        return $this;
    }

    public function to($filterToUse = null)
    {
        $data = [];

        $this->autoloadAll();

        foreach ($this->vos as $name => $vo) {
            $data[$name] = $vo->to($this->data[$name], $filterToUse);
        }

        foreach ($this->getExportFilters()->offsetGet($filterToUse) as $filter) {
            $data = $filter($data);
        }

        return $data;
    }

    /**
     * @deprecated
     */
    public function fill($data, $mapper = null)
    {
        if (!$data) {
            return $this;
        }

        if ($mapper && isset($this->mappers[$mapper])) {
            $data = $this->makeArrayFromAnything($data);
            $data = $this->mappers[$mapper]->map($data);
        }

        if (is_array($data) || is_object($data)) {
            foreach ($data as $name => $value) {
                $this->__set($name, $value);
            }
        }

        return $this;
    }

    /**
     * @deprecated
     */
    public function toArray($mapper = null)
    {
        $array = array();

        foreach ($this->data as $name => $value) {
            if ($value instanceof AccessibleInterface) {
                $value = $value->toArray($mapper);
            }

            $array[$name] = $value;
        }

        if ($mapper && isset($this->mappers[$mapper])) {
            $array = $this->mappers[$mapper]->map($array);
        }

        return $array;
    }

    public function validate()
    {
        $messages = [];

        foreach ($this->vos as $name => $vo) {
            if ($voMessages = $vo->validate($this->data[$name])) {
                $messages = array_merge($messages, $voMessages);
            }
        }

        foreach ($this->validators as $message => $validator) {
            if ($validator($this) === false) {
                $messages[] = $this->validatorMessages[$message];
            }
        }

        foreach ($messages as &$message) {
            foreach ($this->data as $name => $value) {
                if (is_scalar($value)) {
                    $message = str_replace(':' . $name, $value, $message);
                }
            }
        }

        return $messages;
    }

    public function offsetSet($name, $value)
    {
        $this->__set($name, $value);
    }

    public function offsetGet($name)
    {
        return $this->__get($name);
    }

    public function offsetExists($name)
    {
        return $this->__isset($name);
    }

    public function offsetUnset($name)
    {
        $this->__unset($name);
    }

    public function count()
    {
       return count($this->data);
    }

    public function current()
    {
        return current($this->data);
    }

    public function key()
    {
        return key($this->data);
    }

    public function next()
    {
        next($this->data);
    }

    public function rewind()
    {
        reset($this->data);
    }

    public function valid()
    {
        return $this->key() !== null;
    }

    public function serialize()
    {
        $data = [];

        foreach (self::$serializeProperties as $name) {
            $data[$name] = $this->$name;
        }

        return $data;
    }

    public function unserialize($data)
    {
        foreach (self::$serializeProperties as $name) {
            $this->$name = $data[$name];
        }
    }

    private function makeArrayFromAnything($data)
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            $arr = [];

            foreach ($data as $k => $v) {
                $arr[$k] = $v;
            }

            return $arr;
        }

        return [];
    }

    private function configure()
    {
        if ($this->hasCache()) {
            $this->applyCache();
        } else {
            $this->configureUsingAnnotations();
            $this->generateCache();
        }

        $this->removePublicProperties();
    }

    private function configureUsingAnnotations()
    {
        $conf = new EntityConfigurator;
        $conf->__invoke($this);

        $conf = new VoConfigurator;
        $conf->__invoke($this);
    }

    private function removePublicProperties()
    {
        foreach ($this->data as $name => $vo) {
            unset($this->$name);
        }
    }

    private function hasCache()
    {
        $cacheKey = $this->generateCacheKey();
        return isset(self::$cache[$cacheKey]);
    }

    private function applyCache()
    {
        $cacheKey = $this->generateCacheKey();

        foreach (self::$cacheProperties as $name) {
            $this->$name = self::$cache[$cacheKey][$name];
        }

        foreach ($this->vos as $name => $vo) {
            $this->data[$name] = $vo->init();

            if (isset(self::$cache[$cacheKey]['initialValues'][$name])) {
                $this->__set($name, self::$cache[$cacheKey]['initialValues'][$name]);
            }
        }
    }

    private function generateCache()
    {
        $cacheKey = $this->generateCacheKey();

        foreach (self::$cacheProperties as $name) {
            self::$cache[$cacheKey][$name] = $this->$name;
        }

        foreach ($this->data as $name => $vo) {
            if ($this->$name) {
                self::$cache[$cacheKey]['initialValues'][$name] = $this->$name;
            }
        }
    }

    private function generateCacheKey()
    {
        return get_class($this);
    }
}
