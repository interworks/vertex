<?php

namespace app\models;

use Exception;
use DateTime;
use vox\core\Registry;

/** @Entity
    @HasLifecycleCallbacks
    @Table(name="tasks")
*/
class Task extends \vox\data\model\Doctrine {
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;
    /** @Column(type="datetime") */
    protected $createTime;
    /** @Column(type="integer") */
    protected $priority = 0;
    /** @Column(type="integer") */
    protected $timeout = 14400;
    /** @Column(type="string") */
    protected $action;
    /** @Column(type="json") */
    protected $params = null;
    /** @Column(type="string") */
    protected $description = '';
    /** @Column(type="string", nullable=true) */
    protected $runner = null;
    /** @Column(type="boolean") */
    protected $successful = false;
    /** @Column(type="text", nullable=true) */
    protected $results = null;
    /** @Column(type="datetime", nullable=true) */
    protected $lockTime = null;
    /** @Column(type="datetime", nullable=true) */
    protected $startTime = null;
    /** @Column(type="datetime", nullable=true) */
    protected $endTime = null;

    public function __construct() {
        $this->setCreateTime(new DateTime());
    }

    public function hasTimedOut() {
        $start = ($this->getStartTime() ?: $this->getLockTime());

        if (!$start) {
            return false;
        }

        return ((time() - $start->getTimestamp()) > $this->getTimeout());
    }

    public function run() {
        $this->setSuccessful($this->{$this->action}());
    }

    public function analyzeStatistics() {
        if (!is_string($this->params)) {
            $this->results = 'Invalid parameter';
            return false;
        }

        echo "Analyzing statistics..\n";
        $cluster = Registry::getInstance()->cluster;
        $cluster->analyzeStatistics($this->params);

        return true;
    }

    public function sleep() {
        if (!is_int($this->params)) {
            $this->results = 'Invalid parameter';
            return false;
        }

        echo "SLEEPING FOR: " . $this->params . "\n";
        sleep($this->params);

        return true;
    }

    public function lock() {
        $this->setRunner(getmypid());
        $this->setLockTime(new DateTime());
    }

    public function start() {
        $this->setStartTime(new DateTime());
    }

    public function end() {
        $this->setEndTime(new DateTime());
    }
}