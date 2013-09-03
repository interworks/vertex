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

class ObjectController extends ControllerAbstract {
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

        $qs = "SELECT 
                s.*, 
                ZEROIFNULL(num_tables) num_tables, 
                ZEROIFNULL(num_views) num_views 
            FROM schemata s 
            LEFT JOIN (
                SELECT table_schema_id schema_id, COUNT(*) num_tables FROM v_catalog.tables GROUP BY 1
            ) t USING (schema_id) 
            LEFT JOIN (
                SELECT table_schema_id schema_id, COUNT(*) num_views FROM v_catalog.views GROUP BY 1
            ) v USING (schema_id)
            WHERE is_system_schema = 'f'
            ORDER BY schema_name
        ";

        $schemas = $this->_cluster->query($qs);

        $this->view->assign(compact('dbName', 'schemas'));
    }

    public function schemaAction() {
        $schema = $this->_getParam('schemaname');
        $schema = preg_replace('/\W+/', '', $schema);

        $qs = "SELECT t.*, ZEROIFNULL(num_projections) num_projections 
            FROM v_catalog.tables t 
            LEFT JOIN (
                SELECT anchor_table_id table_id, COUNT(*) num_projections FROM v_catalog.projections GROUP BY 1
            ) p USING (table_id) 
            WHERE table_schema = ?
            ORDER BY table_name
        ";
        $tables = $this->_cluster->query($qs, [$schema]);

        $qs = "SELECT * FROM v_catalog.views WHERE table_schema = ? ORDER BY table_name";
        $views = $this->_cluster->query($qs, [$schema]);

        $this->view->assign(compact('tables', 'views', 'schema'));
    }

    public function tableAction() {
        $schema = $this->_getParam('schema');
        $schema = preg_replace('/\W+/', '', $schema);
        $table = $this->_getParam('table');
        $table = preg_replace('/\W+/', '', $table);

        $qs = "SELECT p.*
            FROM v_catalog.projections p
            WHERE projection_schema = ? AND anchor_table_name = ?
            ORDER BY projection_name
        ";
        $projections = $this->_cluster->query($qs, [$schema, $table]);

        $this->view->assign(compact('projections', 'schema', 'table'));
    }

    public function schemaExportAction() {
        $schema = $this->_getParam('schemaname');
        $schema = preg_replace('/\W+/', '', $schema);

        $dump = $this->_cluster->exportObjects($schema);
        header('Content-type: text/plain');
        echo $dump;
        die();
    }
}
