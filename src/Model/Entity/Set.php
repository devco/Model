<?php

namespace Model\Entity;
use Closure;
use InvalidArgumentException;
use Model\Filter\Filterable;
use Model\Filter\FilterableInterface;
use Model\Validator\Validatable;
use Model\Validator\ValidatableInterface;
use RuntimeException;

class Set implements AccessibleInterface, ValidatableInterface
{
    use Validatable;

    private $class;

    private $data = [];

    public function __construct($class, $data = [], $filterToUse = null)
    {
        $this->class = $class;
        $this->from($data, $filterToUse);
    }

    public function clear()
    {
        $this->data = [];
        return $this;
    }

    public function isRepresenting($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        return $this->class === $class;
    }

    public function mustRepresent($class)
    {
        if (!$this->isRepresenting($class)) {
            $class = is_object($class) ? get_class($class) : $class;
            throw new RuntimeException(
                'The entity set is representing "'
                . $this->class
                . '" not "'
                . $class
                . '".'
            );
        }

        return $this;
    }

    public function validate()
    {
        $messages   = [];
        $validators = $this->getValidators();

        $this->walk(function($entity) use (&$messages, $validators) {
            foreach ($validators as $message => $validator) {
                $messages = array_merge($messages, $entity->validate());
            }
        });

        return $messages;
    }

    public function from($data, $filterToUse = null)
    {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $k => $v) {
                $this->offsetSet(null, $this->ensureEntity($v, $filterToUse));
            }
        }

        return $this;
    }

    public function to($filterToUse = null)
    {
        $array = [];

        foreach ($this as $k => $v) {
            $array[$k] = $v->to($filterToUse);
        }

        return $array;
    }

    /**
     * @deprecated
     */
    public function fill($data, $mapper = null)
    {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $k => $v) {
                $this->offsetSet(null, $this->ensureEntity($v, $mapper));
            }
        }

        return $this;
    }

    /**
     * @deprecated
     */
    public function toArray($mapper = null)
    {
        $array = [];

        foreach ($this as $k => $v) {
            $array[$k] = $v->toArray($mapper);
        }

        return $array;
    }

    public function walk($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('The passed argument is not callable.');
        }

        foreach ($this as $entity) {
            call_user_func($callback, $entity);
        }

        return $this;
    }

    public function aggregate($field)
    {
        $values = [];

        foreach ($this as $item) {
            $values[] = $item->__get($field);
        }

        return $values;
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
        $item  = $this->ensureEntity($item);

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
        if (!is_callable($query) && (is_array($query) || is_object($query))) {
            $query = function($item) use ($query) {
                foreach ($query as $name => $value) {
                    if ($value !== $item->__get($name)) {
                        return false;
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

            if ($query($item) !== false) {
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
        if (!$value instanceof Entity) {
            $value = $this->ensureEntity($value);
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
            'class'      => $this->class,
            'data'       => $this->to(),
            'validators' => $this->validators
        ]);
    }

    public function unserialize($data)
    {
        $data             = unserialize($data);
        $this->class      = $data['class'];
        $this->validators = $data['validators'];
        $this->from($data['data']);
    }

    public function limit($limit, $offset)
    {
        $limit  = intval($limit);
        $offset = intval($offset);

        $keys = [];

        foreach ($this as $key => $item) {
            if ($offset && $offset > $key) {
                continue;
            }

            if ($limit && $limit === count($keys)) {
                break;
            }

            $keys[] = $key;
        }

        return $this->reduce($keys);
    }

    private function ensureEntity($item, $filterToUse = null)
    {
        if (!$item instanceof $this->class) {
            $item = new $this->class($item, $filterToUse);
        }

        return $item;
    }

    public function usort($callback)
    {
        if (!is_callable($callback)) {
            return false;
        }

        return usort($this->data, $callback);
    }

    public function ufilter($callback)
    {
        if (!is_callable($callback)) {
            return false;
        }

        $this->data = array_values(array_filter($this->data, $callback));
    }

    public function unique()
    {
      $this->data = array_values(array_unique($this->data));
    }
}
