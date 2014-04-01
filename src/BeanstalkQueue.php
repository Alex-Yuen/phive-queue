<?php

namespace Phive\Queue;

use Pheanstalk_Exception_ServerException as ServerException;
use Pheanstalk_PheanstalkInterface as Pheanstalk;

class BeanstalkQueue implements Queue
{
    /**
     * @var Pheanstalk
     */
    private $client;

    /**
     * @var string
     */
    private $tubeName;

    public function __construct(Pheanstalk $client, $tubeName)
    {
        $this->client = $client;
        $this->tubeName = $tubeName;
    }

    public function getClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function push($item, $eta = null)
    {
        $delay = (null !== $eta) ? QueueUtils::normalizeEta($eta) - time() : 0;

        $this->client->putInTube($this->tubeName, $item, Pheanstalk::DEFAULT_PRIORITY, $delay);
    }

    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        if (!$item = $this->client->reserveFromTube($this->tubeName, 0)) {
            throw new NoItemAvailableException($this);
        }

        $this->client->bury($item);

        return $item->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $stats =  $this->client->statsTube($this->tubeName);

        return $stats->current_jobs_ready;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->doClear('ready');
        $this->doClear('buried');
        $this->doClear('delayed');
    }

    protected function doClear($state)
    {
        try {
            while ($item = $this->client->{'peek'.$state}($this->tubeName)) {
                $this->client->delete($item);
            }
        } catch (ServerException $e) {
        }
    }
}