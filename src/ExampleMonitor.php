<?php

namespace Zwp\Monitor;

use Zwp\Monitor\Counter\CounterManager;
use Zwp\Monitor\Traits\SingletonTrait;

class ExampleMonitor extends BaseMonitor
{
    use SingletonTrait;

    /**
     * 监控名称
     *
     * @return string
     */
    protected function getCounterName()
    {
        return 'exampleCounterName';
    }

    /**
     * 获取调用的方法名称
     *
     * @return mixed|string
     */
    protected function getMethod()
    {
        return 'exampleMethod';
    }

    /**
     * 一次调用增加的负担分数
     *
     * @return int;
     */
    protected function getScore()
    {
        return 1;
    }

    /**
     * 超时时间（毫秒）
     *
     * @return int
     */
    protected function timeoutLimit()
    {
        return 3000;
    }

    /**
     * 是否打印超时日志
     *
     * @return bool
     */
    protected function isLogTimeOut()
    {
        return false;
    }

    /**
     * 获取扩展维度的值
     *
     * @return array|int[]
     */
    protected function getField()
    {
        $counterManager = CounterManager::instance();
        return [
                'score' => $counterManager->currentScore(),
            ] + $counterManager->currentInvokerCount();
    }

}