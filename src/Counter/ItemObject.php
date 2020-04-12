<?php

namespace Zwp\Monitor\Counter;

/**
 * Class ItemObject
 * @package GaiaCore\Lib\Counter
 */
trait ItemObject
{
    /**
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * @param $offset
     * @return bool
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * @param $offset
     * @param $value
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * @param $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __get($name)
    {
        if (method_exists($this, 'get'.ucfirst($name))) {
            return $this->{'get'.ucfirst($name)}();
        }

        return false;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (method_exists($this, 'set'.ucfirst($name))) {
            $this->{'set'.ucfirst($name)}($value);
        }
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->$key);
    }
}