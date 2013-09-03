<?php

namespace app\controllers;

use app\extensions\security\Role;
use app\models\Submission;
use app\models\submission\Status as SubmissionStatus;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\ResultSetMapping;
use Zend_Barcode;
use app\models\Chapter;
use app\models\Membership;
use app\models\Position;
use vox\core\Collection;
use vox\core\Registry;
use vox\data\vertica\Cluster;

class StatusController extends ControllerAbstract {
    protected $_db = null;
    protected $_cluster = null;

    public function preDispatch() {
        parent::preDispatch();

        $this->_cluster = Registry::getInstance()->cluster;
        $this->_cluster->connect();
        $this->_db = $this->_cluster->getDb();
    }

    public function indexAction() {
        $dbName = $this->_cluster->getDbName();
        $nodes = $this->_cluster->getNodes();

        $cpuUsage = [];
        $qs = "SELECT 
                node_name, 
                end_time, 
                average_cpu_usage_percent 
            FROM v_monitor.cpu_usage 
            WHERE end_time > TIMESTAMPADD('hour', -2, CURRENT_TIMESTAMP) 
            ORDER BY node_name, end_time
        ";

        foreach ($this->_cluster->query($qs) as $row) {
            $cpuUsage[$row['node_name']][] = [strtotime($row['end_time'])*1000, $row['average_cpu_usage_percent']];
        }

        $memUsage = [];
        $qs = "SELECT 
                node_name, 
                end_time, 
                average_memory_usage_percent 
            FROM v_monitor.memory_usage 
            WHERE end_time > TIMESTAMPADD('hour', -2, CURRENT_TIMESTAMP) 
            ORDER BY node_name, end_time
        ";

        foreach ($this->_cluster->query($qs) as $row) {
            $memUsage[$row['node_name']][] = [strtotime($row['end_time'])*1000, $row['average_memory_usage_percent']];
        }

        $ioUsage = [];
        $qs = "SELECT 
                node_name,
                end_time,
                read_kbytes_per_sec+written_kbytes_per_sec total_kbytes_per_sec
            FROM v_monitor.io_usage 
            WHERE end_time > TIMESTAMPADD('hour', -2, CURRENT_TIMESTAMP) 
            ORDER BY node_name, end_time
        ";

        foreach ($this->_cluster->query($qs) as $row) {
            $ioUsage[$row['node_name']][] = [strtotime($row['end_time'])*1000, $row['total_kbytes_per_sec']];
        }

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

        $this->view->assign(compact('nodes', 'dbName', 'version', 'schemas', 'cpuUsage', 'memUsage', 'ioUsage', 'startTime'));
    }

    public function storageAction() {
        $dbName = $this->_cluster->getDbName();
        $disks = $this->_cluster->query('SELECT * FROM disk_storage ORDER BY storage_usage, node_name');
        $this->view->assign(compact('dbName', 'disks'));

    }
}
