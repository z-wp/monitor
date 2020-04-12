# monitor
This is a PHP performance monitoring extension

## example

```php
require 'vendor/autoload.php';

\Zwp\Monitor\ExampleMonitor::instance()->start();
sleep(1);
\Zwp\Monitor\ExampleMonitor::instance()->terminal();

// monitor中可指定监控对象、权重、超时时间与超时日志形式、rhea配置等
// 不同的monitor会分类收集到Counter中最后一次性上报给rhea，可用于grafana
```