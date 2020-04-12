<?php

namespace Zwp\Monitor;

use Zwp\Monitor\Counter\CounterManager;

abstract class BaseMonitor
{
    protected $start = null;

    /* @var string $method */
    protected $method;

    /* @var array $parameters */
    protected $parameters;

    /**
     * 在主逻辑调用结束前执行
     *
     * @param String $method
     * @param array $parameters
     * @return bool
     */
    final public function start(String $method = '', Array $parameters = [])
    {
        $this->start = microtime(true) * 1000;
        $this->method = $method;
        $this->parameters = $parameters;
        return true;
    }

    /**
     * 在主逻辑调用结束后执行
     */
    final public function terminal()
    {
        [$name, $spent, $score, $tags, $field] =
            [
                $this->getCounterName(),
                microtime(true) * 1000 - $this->start,
                $this->getScore(),
                $this->getTags(),
                $this->getField()
            ];

        CounterManager::instance()->incInvocation($name, $spent, $score, $tags, $field);

        $timeLimit = $this->timeoutLimit();
        if ($spent > $timeLimit) {
            CounterManager::instance()->incTimeout($name, $spent, $score, $tags, $field);
            if ($this->isLogTimeOut() && $score > 0) {
                $this->logTimeOut($spent, $timeLimit, $name);
            }
        }
    }

    /**
     * 每项单独记录超时日志
     */
    protected function logTimeOut($spent, $timeLimit, $name)
    {
        $level = $spent > 5 * $timeLimit ? LOG_ERR : LOG_WARNING;
        $param = substr(json_encode($this->parameters), 0, 200);
        if ($spent > $timeLimit) {
            // write your timeout log

        }
    }

    /**
     * 一次调用增加的负担分数
     * @return int;
     */
    protected function getScore()
    {
        return 0;
    }

    /**
     * 返回超时规则（可以根据方法进行特殊指定）
     * @return int
     */
    protected function timeoutLimit()
    {
        return 1000;
    }

    /**
     * 是否将超时信息记录日志
     * @return bool
     */
    protected function isLogTimeOut()
    {
        return true;
    }

    /**
     * 返回监控名称
     * @return string
     */
    protected function getCounterName()
    {
        $arr = explode('\\', get_called_class());
        $name = array_pop($arr);
        return substr($name, 0, strpos($name, 'Monitor'));
    }

    /**
     * 获取调用的方法名称
     * @return string
     */
    protected function getMethod()
    {
        return $this->method;
    }

    /**
     * 获取标签
     * @return mixed
     */
    protected function getTags()
    {
        return [
            'operation' => $this->getMethod()
        ];
    }

    /**
     * 获取扩展维度的值
     */
    protected function getField()
    {
        return [];
    }

    /**
     * 获取描述
     * @return mixed
     */
    protected function getDescription()
    {
        return [
            'func'      => get_called_class() . '::' . $this->method,
            'arguments' => $this->parameters
        ];
    }
}