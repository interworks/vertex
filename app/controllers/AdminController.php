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

class AdminController extends ControllerAbstract {
    protected $_db = null;
    protected $_cluster = null;

    public function preDispatch() {
        parent::preDispatch();

        $this->_cluster = Registry::getInstance()->cluster;
        $this->_cluster->connect();
        $this->_db = $this->_cluster->getDb();
    }

    public function indexAction() {
        $configParameters = $this->_cluster->getConfigParameters();
        $this->view->assign(compact('configParameters'));
    }

    public function dataCollectorAction() {
        $qs = "SELECT 
                component,
                MIN(memory_buffer_size_kb) AS memory_buffer_size_kb,
                MIN(disk_size_kb) AS disk_size_kb,
                MIN(first_time) AS first_time,
                MAX(last_time) AS last_time,
                description
            FROM v_monitor.data_collector
            GROUP BY component, description
            ORDER BY component
        ";
        $components = $this->_cluster->query($qs);
        $this->view->assign(compact('components'));
    }
}
