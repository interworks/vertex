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

class AnalyzerController extends ControllerAbstract {
    protected $_db = null;
    protected $_cluster = null;
    protected $_dbName = 'testdb';
    protected $_dbHost = '192.168.75.129';
    // protected $_dbHost = '192.168.254.11';

    public function preDispatch() {
        parent::preDispatch();

        $dbName = $this->_dbName;
        $dbHost = $this->_dbHost;
        $driverPath = '/opt/vertica/lib/libverticaodbc.dylib';
        $dbUser = 'dbadmin';
        $dbPassword = 'password';
        $this->_cluster = new Cluster(compact('dbName', 'dbHost', 'driverPath', 'dbUser', 'dbPassword'));
        $this->_cluster->connect();
        $this->_db = $this->_cluster->getDb();
    }

    public function indexAction() {
        $recommendations = [];

        foreach ($this->_db->query("SELECT * FROM (SELECT ANALYZE_WORKLOAD('')) q ORDER BY last_observation_time DESC, observation_count DESC") as $row) {
            $recommendations[] = $row;
        }

        $this->view->assign(compact('recommendations'));
    }
}
