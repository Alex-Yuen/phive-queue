<?php

namespace Phive\Queue\Tests\Handler;

use Phive\Queue\RedisQueue;

class RedisHandler extends Handler
{
    /**
     * @var \Redis
     */
    private $redis;

    public function createQueue()
    {
        return new RedisQueue($this->redis);
    }

    public function clear()
    {
        $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        $prefix = $this->redis->getOption(\Redis::OPT_PREFIX);
        $offset = strlen($prefix);

        $keys = $this->redis->keys('*');
        foreach ($keys as $key) {
            $this->redis->del(substr($key, $offset));
        }
    }

    protected function configure()
    {
        $this->redis = new \Redis();
        $this->redis->connect($this->getOption('host'), $this->getOption('port'));
        $this->redis->setOption(\Redis::OPT_PREFIX, $this->getOption('prefix'));
    }
}
