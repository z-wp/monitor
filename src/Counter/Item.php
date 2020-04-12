<?php

namespace Zwp\Monitor\Counter;

class Item implements \ArrayAccess
{
    use ItemObject;

    const COUNTER_TYPE_DEFAULT = '';

    /**
     * 可以通过数组形式访问的属性列表：
     */
    const FIELD_SHORT_NAME      = 'shortName';
    const FIELD_MEASUREMENT     = 'measurement';
    const FIELD_SCORE           = 'score';
    const FIELD_OPERATION       = 'operation';
    const FIELD_DURATION        = 'duration';
    const FIELD_TAGS            = 'tags';
    const FIELD_FIELDS          = 'fields';

    /* @var string $type */
    protected $invoker;

    /* @var string $type */
    protected $type;

    /* @var int $duration */
    protected $duration;

    /* @var int $score */
    protected $score;

    /* @var array $tags */
    protected $tags;

    /* @var array $fields */
    protected $fields;

    /**
     * Item constructor.
     * @param $invoker
     * @param $type
     * @param $duration
     * @param $score
     * @param $tags
     * @param $fields
     */
    public function __construct($invoker, $type, $duration, $score, $tags, $fields = [])
    {
        $this->type     = $type;
        $this->tags     = $tags;
        $this->score    = $score;
        $this->invoker  = $invoker;
        $this->fields   = $fields;
        $this->duration = $duration;
    }

    /**
     * 获取调用器短名称 ['shortName']
     * @see self::FIELD_SHORT_NAME
     *
     * @return mixed
     */
    public function getShortName()
    {
        return $this->invoker;
    }

    /**
     * 获取这次调用消耗的时间 ['duration']
     * @see self::FIELD_DURATION
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * 获取这次调用消耗的时间 ['tags']
     * @see self::FIELD_TAGS
     *
     * @return array
     */
    public function getTags()
    {
        if (is_callable($this->tags)) {
            return ($this->tags)();
        }
        return $this->tags;
    }

    /**
     * 获取这次调用消耗的时间 ['fields']
     * @see self::FIELD_FIELDS
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * 获取 InfluxDB 写入表明 ['measurement']
     * @see self::FIELD_MEASUREMENT
     *
     * @return bool|string
     */
    public function getMeasurement()
    {
        $shortName = $this['shortName'];
        if ($this->type == static::COUNTER_TYPE_DEFAULT) {
            return $shortName;
        }
        return $shortName . "." . $this->type;
    }

    /**
     * 获取这一次调用的负担分数 ['score']
     * @see self::FIELD_SCORE
     *
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * 获取这一次的调用操作 ['operation']
     * @see self::FIELD_OPERATION
     *
     * @return string
     */
    public function getOperation()
    {
        return isset($this->tags['operation']) ? $this->tags['operation'] : 'generic';
    }

}