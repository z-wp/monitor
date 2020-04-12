<?php

namespace Zwp\Monitor\Counter;

use Zwp\Monitor\Traits\SingletonTrait;
use Zwp\Monitor\Support\Collection;

/**
 * Class CounterManager
 * @package GaiaCore\Lib\Counter
 */
class CounterManager
{
    use SingletonTrait;

    /** 最大 flush 间隔 */
    const MAX_FLUSH_INTERVAL = 60;

    /* @var bool */
    protected $disabled;

    /* @var Collection $collection ：调用记录集合 */
    protected $collection;

    /* @var int $score ：累计负担分数*/
    protected $score = 0;

    /* @var int[] $invokerCount ：调用统计 */
    protected $invokerCount = [];

    /* @var int $startTime ：上次缓冲清空的时间 */
    protected $startTime;

    /** @var string $rheaServer : rhea服务地址 */
    protected $rheaServer;

    /** @var string $rheaAppName : 上报的服务名称 */
    protected $rheaAppName;

    /**
     * CounterManager constructor.
     */
    public function __construct()
    {
        $this->collection = Collection::make([]);
        $this->startTime = microtime(true);
    }

    /**
     * CounterManager destructor.
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * 设置rhea服务配置
     *
     * @param $rheaServer
     * @param $rheaAppName
     */
    public function setRheaConfig($rheaServer, $rheaAppName)
    {
        if (is_null($this->rheaServer)) {
            $this->rheaServer = $rheaServer;
        }
        if (is_null($this->rheaAppName)) {
            $this->rheaAppName = $rheaAppName;
        }
    }

    /**
     * 记录调用
     *
     * @param $invoker
     * @param $duration
     * @param $score
     * @param array $tags
     * @param array $attributes
     */
    public function incInvocation($invoker, $duration, $score, $tags, $attributes = [])
    {
        $this->inc($invoker, $duration, $score, $tags, $attributes, 'invocation');
    }

    /**
     * 记录超时调用
     *
     * @param $invoker
     * @param $duration
     * @param $score
     * @param array $tags
     * @param array $attributes
     */
    public function incTimeout($invoker, $duration, $score, $tags, $attributes = [])
    {
        $this->inc($invoker, $duration, $score, $tags, $attributes, 'timeout');
    }

    /**
     * 记录并指定 Type
     *
     * @param $invoker
     * @param $duration
     * @param $score
     * @param $type
     * @param array $tags
     * @param array $attributes
     */
    public function incWithType($invoker, $duration, $type, $score, $tags, $attributes = [])
    {
        $this->inc($invoker, $duration, $score, $tags, $attributes, $type);
    }

    /**
     * 增加记录
     *
     * @param $invoker
     * @param $duration
     * @param $score
     * @param $tags
     * @param $attributes
     * @param $type
     */
    protected function inc($invoker, $duration, $score, $tags, $attributes, $type)
    {
        if (true === $this->disabled) {
            return;
        }

        $counter = new Item($invoker, $type, $duration, $score, $tags, $attributes);

        /** 统计分数 */
        $this->score += $score;

        /** 统计调用次数 */
        if (!array_key_exists($invoker, $this->invokerCount)) {
            $this->invokerCount[$invoker] = 0;
        }
        $this->invokerCount[$invoker]++;

        /** 存入统计节点缓冲 */
        $this->collection->push($counter);

        if ($this->checkCanFlush()) {
            $this->flush();
        }
    }

    /**
     * 获取上次 flush 至今为止累计调用的负担分数
     *
     * @return int
     */
    public function currentScore()
    {
        return $this->score;
    }

    /**
     * 获取上次 flush 至今为止累计的调用统计
     *
     * @return \int[]
     */
    public function currentInvokerCount()
    {
        return $this->invokerCount;
    }

    /**
     * 将统计缓冲输出至 Rhea
     *
     * @return bool
     */
    public function flush()
    {
        if ($this->collection->isEmpty()) {
            return true;
        }

        $data = $this->monitorData();
        $this->refresh();

        if ($this->rheaServer) {
            return Rhea::instance()->setConfig($this->rheaServer, $this->rheaAppName)->flush($data);
        }
        return true;
    }

    /**
     * 将缓冲清空
     */
    public function refresh()
    {
        $this->score        = 0;
        $this->startTime    = microtime(true);
        $this->collection   = Collection::make([]);
        $this->invokerCount = [];
    }

    /**
     * 检查是否需要输出缓冲区统计
     *
     * @return bool
     */
    protected function checkCanFlush()
    {
        return microtime(true) - $this->startTime > static::MAX_FLUSH_INTERVAL;
    }

    /**
     * 获取详细统计信息
     *
     * @return array
     */
    public function statistics()
    {
        $data = [];
        foreach ($this->monitorData() as $name => $counters) {
            foreach ($counters as $item) {
                $data[] = ['name' => $name] + $item;
            }
        }

        return $data;
    }

    /**
     * 获取监控数据
     *
     * @return array
     */
    protected function monitorData()
    {
        $result  = [];

        $measurementGroup = $this->collection->groupBy(Item::FIELD_MEASUREMENT);

        $operationGroup = $measurementGroup->map( function(Collection $measurementItems) {
            return $measurementItems->groupBy(Item::FIELD_OPERATION);
        });

        $operationGroup->each(
            function(Collection $operationMeasurementGroup, $fKey) use (&$result)
            {
                $operationMeasurementGroup->each(
                    function (Collection $group, $sKey) use ($fKey, &$result)
                    {
                        $first = $group->first();

                        $result[$fKey][$sKey] = [
                            'value'     => $group->count(),
                            'timestamp' => time(),
                            'max'       => $group->max(Item::FIELD_DURATION),
                            'min'       => $group->min(Item::FIELD_DURATION),
                            'sum'       => $group->sum(Item::FIELD_DURATION),
                            'duration'  => $group->avg(Item::FIELD_DURATION, "%.2f"),
                            'tags'      => $first[Item::FIELD_TAGS],
                            'fields'    => $first[Item::FIELD_FIELDS],
                        ];
                    }
                );
            }
        );

        return $result;
    }

    /**
     * 获取监控数据
     *
     * @return array|bool
     */
    public function getMonitorData()
    {
        if ($this->collection->isEmpty()) {
            return true;
        }

        return $this->monitorData();
    }

    public function getFormatMonitorData()
    {
        if ($this->collection->isEmpty()) {
            return true;
        }

        $result  = [];

        $measurementGroup = $this->collection->groupBy(Item::FIELD_MEASUREMENT);

        $operationGroup = $measurementGroup->map( function(Collection $measurementItems) {
            return $measurementItems->groupBy(Item::FIELD_OPERATION);
        });

        $operationGroup->each(
            function(Collection $operationMeasurementGroup, $fKey) use (&$result)
            {
                $operationMeasurementGroup->each(
                    function (Collection $group) use ($fKey, &$result)
                    {
                        $first = $group->first();

                        $result[] = [
                            'name' => $fKey,
                            'value'     => $group->count(),
                            'timestamp' => time(),
                            'max'       => $group->max(Item::FIELD_DURATION),
                            'min'       => $group->min(Item::FIELD_DURATION),
                            'sum'       => $group->sum(Item::FIELD_DURATION),
                            'duration'  => $group->avg(Item::FIELD_DURATION, "%.2f"),
                            'tags'      => $first[Item::FIELD_TAGS],
                            'fields'    => $first[Item::FIELD_FIELDS],
                        ];
                    }
                );
            }
        );

        return $result;
    }
}
