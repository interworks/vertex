<?php

namespace app\controllers;

use app\extensions\security\Role;
use app\models\Submission;
use app\models\submission\Status as SubmissionStatus;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\ResultSetMapping;
use Zend_Barcode;
use app\models\Task;
use vox\core\Collection;
use vox\core\Registry;
use vox\data\vertica\Cluster;
use DateTime;

class IndexController extends ControllerAbstract {
    protected $_db = null;
    protected $_cluster = null;

    public function preDispatch() {
        parent::preDispatch();

        $this->_cluster = Registry::getInstance()->cluster;
        $this->_cluster->connect();
        $this->_db = $this->_cluster->getDb();
    }

    /**
     * @todo Finish task killing for timeouts
     */
    public function testAction() {
        $em = Task::getEm();

        // Should run
        $task = new Task();
        $task->set([
            'action'      => 'analyzeStatistics',
            'params'      => '',
            'timeout'     => 2,
            'description' => 'Analyze Statistics on all projections',
        ]);
        $em->persist($task);

        // $task = new Task();
        // $task->setAction('DO NOT RUN (KILL)');
        // $task->lock();
        // $task->setTimeout(1);
        // $em->persist($task);

        $em->flush();

        // Check for any tasks that are currently running. If they are, check for timeouts.
        $query = $em->createQuery("SELECT t FROM app\models\Task t WHERE t.endTime IS NULL AND t.lockTime IS NOT NULL");
        $taskFound = false;

        foreach ($query->getResult() as $task) {
            $taskFound = true;

            if ($task->hasTimedOut()) {
                // echo "Need to kill task " . $task->getId() . "\n";
                // $task->end();
                // $em->persist($task);
                // $em->flush();

                // $pid = (int) $task->getRunner();

                // if ($pid) {
                //     posix_kill($pid, SIGTERM);
                // }
            }
        }

        $em->flush();

        if ($taskFound) {
            echo "Quitting..\n";
            return;
        }

        // Find tasks that are ready to go
        $tasks = Task::findBy(['lockTime' => null], ['priority' => 'ASC', 'createTime' => 'ASC'], 1);

        if (!$tasks) {
            echo "Nothing found.\n";
            return;
        }

        $task = $tasks[0];

        echo "Running task: " . $task->getId() . "\n";

        $task->lock();
        $task->start();
        $em->persist($task);
        $em->flush();

        $task->run();
        $task->end();
        $em->persist($task);
        $em->flush();

        echo "Done.\n";
    }

    public function indexAction() {
        // $m = new \app\models\Member();
        // $m->setFirstName('Josh');
        // $m->setLastName('Varner');
        // $em = $m::getEm();
        // $em->persist($m);
        // $em->flush();
        $tasks = Task::findBy(['endTime' => null], ['priority' => 'ASC', 'createTime' => 'ASC']);

        $dbName = $this->_cluster->getDbName();
        $nodes = $this->_cluster->getNodes();

        $cpuUsage = [];
        $memUsage = [];
        $ioUsage = [];
        $netUsage = [];

        $qs = "SELECT 
                node_name,
                end_time,
                average_memory_usage_percent,
                average_cpu_usage_percent,
                io_read_kbytes_per_second+io_written_kbytes_per_second io_total_kbytes_per_sec,
                net_rx_kbytes_per_second+net_tx_kbytes_per_second net_total_kbytes_per_sec
            FROM v_monitor.system_resource_usage 
            WHERE end_time > TIMESTAMPADD('hour', -2, CURRENT_TIMESTAMP) 
            ORDER BY node_name, end_time
        ";

        foreach ($this->_cluster->query($qs) as $row) {
            $time = strtotime($row['end_time'])*1000;
            $cpuUsage[$row['node_name']][] = [$time, $row['average_cpu_usage_percent']];
            $memUsage[$row['node_name']][] = [$time, $row['average_memory_usage_percent']];
            $ioUsage[$row['node_name']][] = [$time, $row['io_total_kbytes_per_sec']];
            $netUsage[$row['node_name']][] = [$time, $row['net_total_kbytes_per_sec']];
        }

        $disks = $this->_cluster->query('SELECT * FROM disk_storage ORDER BY storage_usage, node_name');

        $schemas = [];

        // foreach ($this->_db->query('SELECT schema_name, schema_owner, system_schema_creator, create_time FROM schemata WHERE is_system_schema = \'f\'') as $row) {
        //     $schemaName = $row['schema_name'];
        //     $row['tables'] = [];

        //     foreach ($this->_db->query("SELECT * FROM tables WHERE table_schema = '{$schemaName}") as $t) {
        //         $table = $t['table_name'];
        //         $projections = array();

        //         foreach ($this->_db->query("SELECT * FROM projections WHERE anchor_table_name = '{$table}' AND projection_schema = '{$schemaName}'") as $p) {
        //             $projections[] = $p;
        //         }

        //         $t['projections'] = $projections;
        //         $row['tables'][$table] = $t;
        //     }

        //     $schemas[$schemaName] = $row;
        // }

        ksort($schemas);

        $version = $this->_cluster->getVersion();
        $startTime = $this->_cluster->getDatabaseStartTime();

        $this->view->assign(compact('nodes', 'dbName', 'version', 'schemas', 'disks', 'cpuUsage', 'memUsage', 'netUsage', 'ioUsage', 'startTime', 'tasks'));
    }

    public function sessionsAction() {
        $dbName = $this->_cluster->getDbName();
        $qs = "SELECT * FROM sessions";
        $sessions = $this->_cluster->query($qs);

        $this->view->assign(compact('sessions'));
    }

    public function queriesAction() {
        $dbName = $this->_cluster->getDbName();

        $qs = "select request_type, date_trunc('minute', time), count(*) from dc_requests_issued group by 1,2 order by 1,2;";
        $queries = $this->_cluster->query($qs);
        
        $this->view->assign(compact('queries'));
    }

    public function systemAction() {
        $dbName = $this->_cluster->getDbName();
        $nodes = $this->_cluster->getNodes();

        $cpuUsage = [];
        $memUsage = [];
        $ioUsage = [];
        $netUsage = [];

        $qs = "SELECT 
                node_name,
                end_time,
                average_memory_usage_percent,
                average_cpu_usage_percent,
                io_read_kbytes_per_second+io_written_kbytes_per_second io_total_kbytes_per_sec,
                net_rx_kbytes_per_second+net_tx_kbytes_per_second net_total_kbytes_per_sec
            FROM v_monitor.system_resource_usage 
            WHERE end_time > TIMESTAMPADD('hour', -2, CURRENT_TIMESTAMP) 
            ORDER BY node_name, end_time
        ";

        foreach ($this->_cluster->query($qs) as $row) {
            $time = strtotime($row['end_time'])*1000;
            $cpuUsage[$row['node_name']][] = [$time, $row['average_cpu_usage_percent']];
            $memUsage[$row['node_name']][] = [$time, $row['average_memory_usage_percent']];
            $ioUsage[$row['node_name']][] = [$time, $row['io_total_kbytes_per_sec']];
            $netUsage[$row['node_name']][] = [$time, $row['net_total_kbytes_per_sec']];
        }

        $this->view->assign(compact('nodes', 'dbName', 'cpuUsage', 'memUsage', 'netUsage', 'ioUsage'));
    }

}
