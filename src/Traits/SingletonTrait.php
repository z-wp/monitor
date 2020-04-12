<?php

namespace Zwp\Monitor\Traits;


trait SingletonTrait
{
    protected static $_instance = null;

    protected function __construct()
    {
        $ref = new \ReflectionClass(static::class);
        if ($ref->hasMethod('init')) {
            $this->init();
        }
    }

    public static function instance()
    {
        if (self::$_instance === null) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }
}