<?php

namespace Model\Entity;
use InvalidArgumentException;
use Model\Filter\Filterable;
use Model\Filter\FilterableInterface;
use Model\Validator\Validatable;
use Model\Validator\ValidatableInterface;
use ReflectionClass;

class VoSet implements AccessibleInterface, ValidatableInterface
{
    use Validatable;

    private $args;

    private $class;

    private $data = [];

    private $vo;

    public function __construct($class, $args = [], $data = [])
    {
        $this->args = $args;
        $this->class = $class;
        $this->vo = (new ReflectionClass($class))->newInstanceArgs($args);
        $this->from($data);
    }

    public function clear()
    {
        $this->data = [];
        return $this;
    }

    public function validate()
    {
        $messages   = [];
        $validators = $this->getValidators();

        $this->walk(function($item) use (&$messages, $validators) {
            foreach ($validators as $message => $validator) {
                $messages = array_merge($messages, $item->validate());
            }
        });

        return $messages;
    }

    public function from($data, $filterToUse = null)
    {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $k => $v) {
                $this->offsetSet(null, $this->ensureVo($v, $filterToUse));
            }
        }

        return $this;
    }

    public function to($filterToUse = null)
    {
        $array = [];

        foreach ($this as $k => $v) {
            $array[$k] = $this->vo->to($v, $filterToUse);
        }

        return $array;
    }

    /**
     * @deprecated
     */
    public function fill($data, $mapper = null)
    {
        return $this->from($data, $mapper);
    }

    /**
     * @deprecated
     */
    public function toArray($mapper = null)
    {
        return $this->to($mapper);
    }

    public function walk($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('The passed argument is not callable.');
        }

        foreach ($this as $item) {
            call_user_func($callback, $item);
        }

        return $this;
    }

    public function moveTo($currentIndex, $newIndex)
    {
        if ($item = $this->offsetGet($currentIndex)) {
            $this->offsetUnset($currentIndex);
            $this->push($newIndex, $item);
        }

        return $this;
    }

    public function push($index, $item = [])
    {
        $start = array_slice($this->data, 0, $index);
        $end   = array_slice($this->data, $index);
        $item  = $this->ensureVo($item);

        $this->data = array_merge($start, [$index => $item], $end);

        return $this;
    }

    public function pull($index)
    {
        if ($item = $this->offsetGet($index)) {
            $this->offsetUnset($index);
            return $item;
        }
    }

    public function prepend($item = [])
    {
        return $this->push(0, $item);
    }

    public function append($item = [])
    {
        return $this->push($this->count(), $item);
    }

    public function filter($query)
    {
        return $this->reduce($this->findKeys($query));
    }

    public function reduce($keys)
    {
        $found = [];

        foreach ((array) $keys as $key) {
            if (isset($this->data[$key])) {
                $found[$key] = $key;
            }
        }

        if (!$found) {
            return $this->clear();
        }

        foreach ($this->data as $key => $value) {
            if (!isset($found[$key])) {
                unset($this->data[$key]);
            }
        }

        $this->data = array_values($this->data);

        return $this;
    }

    public function remove($query)
    {
        foreach ($this->findKeys($query) as $key) {
            unset($this->data[$key]);
        }

        $this->data = array_values($this->data);

        return $this;
    }

    public function findOne($query)
    {
        $clone = clone $this;
        $key   = $clone->findKey($query);

        if ($key !== false) {
            return $clone->reduce($key)->offsetGet(0);
        }

        return false;
    }

    public function find($query, $limit = 0, $offset = 0)
    {
        $clone = clone $this;
        return $clone->reduce($clone->findKeys($query, $limit, $offset));
    }

    public function findKey($query)
    {
        if ($found = $this->findKeys($query, 1)) {
            return $found[0];
        }

        return false;
    }

    public function findKeys($query, $limit = 0, $offset = 0)
    {
        if (!is_callable($query)) {
            if (!(is_array($query) || is_object($query))) {
                $query = [$query];
            }
            
            $query = function($item) use ($query) {
                foreach ($query as $el) {
                    if ($el === $item) {
                        return true;
                    }
                }
            };
        }

        $keys = [];

        foreach ($this as $key => $item) {
            if ($offset && $offset > $key) {
                continue;
            }

            if ($limit && $limit === count($keys)) {
                break;
            }

            if ($query($item)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function first()
    {
        if ($this->offsetExists(0)) {
            return $this->offsetGet(0);
        }
    }

    public function last()
    {
        $lastIndex = $this->count() - 1;

        if ($this->offsetExists($lastIndex)) {
            return $this->offsetGet($lastIndex);
        }
    }

    public function offsetSet($offset, $value)
    {
        if (!$value instanceof Vo) {
            $value = $this->ensureVo($value);
        }

        $offset = is_numeric($offset) ? (int) $offset : count($this->data);

        $this->data[$offset] = $value;

        return $this;
    }

    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->data[$offset];
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->data[$offset]);
            $this->data = array_values($this->data);
        }

        return $this;
    }

    public function count()
    {
        return count($this->data);
    }

    public function current()
    {
        return $this->offsetGet($this->key());
    }

    public function key()
    {
        return key($this->data);
    }

    public function next()
    {
        next($this->data);
        return $this;
    }

    public function rewind()
    {
        reset($this->data);
        return $this;
    }

    public function valid()
    {
        return !is_null($this->key());
    }

    public function serialize()
    {
        return serialize([
            'args'       => $this->args,
            'class'      => $this->class,
            'data'       => $this->to(),
            'validators' => $this->validators
        ]);
    }

    public function unserialize($data)
    {
        $data             = unserialize($data);
        $this->args       = $data['args'];
        $this->class      = $data['class'];
        $this->validators = $data['validators'];
        $this->vo         = (new ReflectionClass($this->class))->newInstanceArgs($this->args);
        $this->from($data['data']);
    }

    private function ensureVo($item, $filterToUse = null)
    {
        if (!$item instanceof $this->class) {
            $item = $this->vo->translate($item);
        }

        return $item;
    }
}
