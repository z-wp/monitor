<?php

namespace Zwp\Monitor\Tests;

use Zwp\Monitor\ExampleMonitor;

class MonitorTest
{
    public function testExampleMonitor()
    {
        ExampleMonitor::instance()->start();
        sleep(1);
        ExampleMonitor::instance()->terminal();
    }
}