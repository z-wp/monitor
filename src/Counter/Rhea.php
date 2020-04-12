<?php

namespace Zwp\Monitor\Counter;

use Zwp\Monitor\Traits\SingletonTrait;
use Google\Protobuf\Internal\CodedOutputStream;
use Google\Protobuf\Internal\GPBWire;
use Zwp\Monitor\Vox\Items;
use Zwp\Monitor\Vox\Item;

class Rhea
{
    use SingletonTrait;

    private $socket;
    private $_serverInfo;
    private $_appName;

    /**
     * @return bool|array
     */
    private function server()
    {
        if (!function_exists('socket_create')) {
            return false;
        }

        if (!isset($this->_serverInfo)) {
            $server = $this->_serverInfo;
            if (!$server) {
                return false;
            }

            $this->_serverInfo = parse_url($server);
        }

        return $this->_serverInfo;
    }

    public function setConfig($server, $appName)
    {
        if (is_null($this->_serverInfo)) {
            $this->_serverInfo = $server;
        }
        if (is_null($this->_appName)) {
            $this->_appName = $appName;
        }
        return $this;
    }

    public function available()
    {
        return class_exists('Google\Protobuf\Internal\GPBWire') && !empty($this->server());
    }

    public function flush($counterStatistics)
    {
        if (!$this->available()) {
            return false;
        }

        $items = new Items();
        $items->setHostname(gethostname());
        $items->setApp($this->_appName);

        foreach ($counterStatistics as $name => $counters) {
            foreach ($counters as $item) {
                $items->getItems()[] = $this->createItem($name, $item);
            }
        }

        if (empty($items->getItems())) {
            return false;
        }

        $byteSize = $items->byteSize();
        $byteSize += GPBWire::varint32Size($byteSize);

        $output = new CodedOutputStream($byteSize);
        GPBWire::writeMessage($output, $items);
        $serializedMsg = $output->getData();

        $ret = $this->send($serializedMsg, $byteSize);

        socket_close($this->socket);
        $this->socket = null;

        return $ret;
    }

    private function createItem($name, $item)
    {
        $profileItem = new Item();
        $profileItem->setTimestamp($item['timestamp']);
        $profileItem->setName($name);
        $profileItem->setValue($item['value']);
        if (isset($item['type'])) {
            $profileItem->setType($item['type']);
        }
        if (!empty($item['min'])) {
            $profileItem->setMin($item['min']);
        }
        if (!empty($item['max'])) {
            $profileItem->setMax($item['max']);
        }
        if (!empty($item['duration'])) {
            $profileItem->setDuration($item['duration']);
        }
        if (!empty($item['tags'])) {
            $profileItem->setTags($item['tags']);
        }
        if (!empty($item['fields'])) {
            $profileItem->setFields($item['fields']);
        }

        return $profileItem;
    }

    private function socket()
    {
        if (is_null($this->socket)) {
            if (!empty($this->server()) && $this->server()['scheme'] == 'udp') {
                $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                if ($this->socket === FALSE) {
                    return null;
                }

                socket_set_nonblock($this->socket);
            }
        }

        return $this->socket;
    }

    public function __destruct()
    {
        if ($this->socket) {
            socket_close($this->socket);
        }
    }

    private function send($bytes, $size)
    {
        $socket = $this->socket();
        if (!$socket) {
            return false;
        }

        if ($this->server()['scheme'] == 'udp') {
            $sent = socket_sendto($socket, $bytes, $size, 0, $this->server()['host'], $this->server()['port']);
            if ($sent != $size) {
                return false;
            }

            return true;
        }

        return false;
    }
}
