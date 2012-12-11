<?php

namespace Model\Mapper;
use Closure;
use Model\Entity\Entity;
use ReflectionClass;

/**
 * The mapping class that maps one array or object to an array.
 * 
 * @category Mapping
 * @package  Model
 * @author   Trey Shugart <treshugart@gmail.com>
 * @license  Copyright (c) 2011 Trey Shugart http://europaphp.org/license
 */
class Mapper implements MapperInterface
{
    /**
     * The wildcard character.
     * 
     * @var string
     */
    const WILDCARD = '*';

    /**
     * The items to copy.
     * 
     * @var array
     */
    public $copy = [];
    
    /**
     * The items to move.
     * 
     * @var array
     */
    public $move = [];
    
    /**
     * Property blacklist.
     * 
     * @var array
     */
    public $blacklist = [];
    
    /**
     * Value filters.
     * 
     * @var array
     */
    public $filters = [];
    
    /**
     * Property whitelist.
     * 
     * @var array
     */
    public $whitelist = [];
    
    /**
     * The internal mapping to convert the input data to.
     * 
     * @var array
     */
    private $map = [];
    
    /**
     * Constructs and initializes the mapper. Auto-configures the mapper based on preset properties and methods.
     * 
     * @return Mapper
     */
    public function __construct()
    {
        // apply copied properties from a $copy property
        foreach ($this->copy as $from => $to) {
            $this->copy($from, $to);
        }
        
        // apply moved properties from a $move property
        foreach ($this->move as $from => $to) {
            $this->move($from, $to);
        }

        // convert filter names to instance methods
        foreach ($this->filters as $from => $to) {
            $this->filter($from, [$this, $to]);
        }
    }
    
    /**
     * Configuration hook.
     * 
     * @return void
     */
    public function configure()
    {
        
    }
    
    /**
     * Sets a source to destination map.
     * 
     * @param string $from The source.
     * @param string $to   The destination.
     * 
     * @return Mapper
     */
    public function copy($from, $to)
    {
        $this->map[$to] = $from;
        return $this;
    }
    
    /**
     * Moves the item to the specified index.
     * 
     * @param string $from The source key.
     * @param string $to   The destination key.
     * 
     * @return Mapper
     */
    public function move($from, $to)
    {
        $this->copy($from, $to)->blacklist($from);
        return $this;
    }
    
    /**
     * Whitelists the specified destination key.
     * 
     * @param string $to The destination key.
     * 
     * @return Mapper
     */
    public function whitelist($to)
    {
        $this->whitelist[] = $to;
        return $this;
    }
    
    /**
     * Blacklists the specified destination key.
     * 
     * @param string $to The destination key.
     * 
     * @return Mapper
     */
    public function blacklist($to)
    {
        $this->blacklist[] = $to;
        return $this;
    }
    
    /**
     * Filters the specified destination key.
     * 
     * @param string   $to The destination key.
     * @param callable $fn The method name to use for filtering.
     * 
     * @return Mapper
     */
    public function filter($to, callable $fn = null)
    {
        if (is_callable($to)) {
            $fn = $to;
            $to = self::WILDCARD;
        }

        $this->filters[$to] = $fn;

        return $this;
    }

    /**
     * Converts the input array to the output array.
     * 
     * @return array
     */
    public function map(array $from)
    {
        // copy all values if there is nothing in the whitelist
        if ($this->whitelist) {
            $to = array();
        } else {
            $to = $from;
        }
        
        // whitelist
        foreach ($this->whitelist as $dest) {
            $this->setMappedValue($dest, $this->getMappedValue($dest, $from), $to);
        }

        // blacklist
        foreach ($this->blacklist as $dest) {
            $this->unsetMappedValue($dest, $to);
        }

        // applies each mapping to each of the elements in the array
        foreach ($this->map as $dest => $src) {
            $this->setMappedValue($dest, $this->getMappedValue($src, $from), $to);
        }
        
        // apply filters
        foreach ($this->filters as $dest => $filter) {
            if ($dest === self::WILDCARD) {
                $filter($from, $to);
            } else {
                $value = $this->getMappedValue($dest, $to);
                $value = $filter($value, $from, $to);
                $this->setMappedValue($dest, $value, $to);
            }
        }

        return $to;
    }

    /**
     * Maps an array of arrays.
     * 
     * @param array $all The array of array's to map.
     * 
     * @return array
     */
    public function mapAll(array $all)
    {
        $out = [];
        foreach ($all as $arr) {
            $out = $this->map($arr);
        }
        return $out;
    }

    /**
     * Maps the value specified value from $from to $to and returns the resulting array.
     * 
     * @param string $map  The from key.
     * @param string $from The array to get the value from.
     * 
     * @return mixed
     */
    private function getMappedValue($map, array $from = array())
    {
        // if this value is set just accept it straight away
        if (isset($from[$map])) {
            return $from[$map];
        }

        // only get the first dot part and the rest still intact
        // this way we can tell if we are at the end
        $parts = explode('.', $map, 2);
        $value = null;

        // if we are NOT at the end of the dot-notated string we continue
        // to return the mapped value using the rest of the dot parts
        // otherwise, we attempt to return the mapped value if it is set
        if (isset($parts[1]) && isset($from[$parts[0]])) {
            $value = $this->getMappedValue($parts[1], $from[$parts[0]]);;
        } elseif (isset($from[$parts[0]])) {
            $value = $from[$parts[0]];
        }
        
        // return a filtered value (if no filter is defined, it is simply passed through)
        return $value;
    }

    /**
     * Sets the specified value using the given map to the specified array.
     * 
     * @param string $map   The map to use to set the value in the array.
     * @param mixed  $value The the value to map.
     * @param array  &$to   The array that we are mapping to.
     * 
     * @return Mapper
     */
    private function setMappedValue($map, $value, array &$to = array())
    {
        // only 2 parts at a time
        $parts = explode('.', $map, 2);

        // check if we are at the end
        // if not, continue to set
        if (isset($parts[1])) {
            $this->modifyKey($parts[0], $to);
            
            // make sure the destination is at least an empty array
            if (!isset($to[$parts[0]])) {
                $to[$parts[0]] = array();
            }
            
            $this->setMappedValue($parts[1], $value, $to[$parts[0]]);
        } else {
            $this->modifyKey($parts[0], $to);
            $this->modifyArray($parts[0], $value, $to);
        }

        // since we modify a reference, we can chain if we want
        return $this;
    }
    
    /**
     * Removes the mapped value.
     * 
     * @param string $map The value key.
     * @param array  &$to The array to remove the value from.
     * 
     * @return Mapper 
     */
    private function unsetMappedValue($map, array &$to)
    {
        $parts = explode('.', $map);
        $last  = array_pop($parts);
        $value = &$to;
        
        foreach ($parts as $part) {
            $value = &$value[$part];
        }
        
        unset($value[$last]);
        
        return $this;
    }

    /**
     * Modifies the array based on the input key and value.
     * 
     * @param string $key   The key to modify.
     * @param mixed  $value The value to modify it with.
     * @param array  &$to   The array being modified.
     * 
     * @return Mapper
     */
    private function modifyArray($key, $value, array &$to)
    {
        $to[$key] = $value;
        return $this;
    }

    /**
     * Detects the type of key and modifies it according to its type. We have to pass in the array that we are mapping
     * to because we need information about it when we are detecting the key and modifying it.
     * 
     * @param mixed &$key The key to modify.
     * @param array $to   The array we are mapping to so we can gather information about it.
     * 
     * @return Mapper
     */
    private function modifyKey(&$key, array $to)
    {
        if ($key === '$') {
            $key = count($to);
        } elseif (is_numeric($key)) {
            $key = (int) $key;
        }
        return $this;
    }
}
