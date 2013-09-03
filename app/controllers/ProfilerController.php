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

class ProfilerController extends ControllerAbstract {
    protected $_db = null;
    protected $_cluster = null;

    public function preDispatch() {
        parent::preDispatch();

        $this->_cluster = Registry::getInstance()->cluster;
        $this->_cluster->connect();
        $this->_db = $this->_cluster->getDb();
    }

    /**
     * @todo Ability to look at other queries that have already been profiled, or keep link to this profile output
     */
    public function indexAction() {
        if ($this->_request->isPost()) {
            $query = $this->_getParam('query');
            $query = rtrim(trim($query), ';');

            if (false !== strpos($query, ';')) {
                die('Query cannot contain a semicolon');
            } elseif (!preg_match('/\bfrom\b/i', $query)) {
                die('Query must contain a FROM clause.');
            }

            $action = $this->request->getPost('action', 'explain');

            $this->view->assign(compact('query', 'action'));

            if ('profile' === $action) {
                $clearCaches = $this->request->getPost('profile-clearCaches', 0);

                if ($clearCaches) {
                    $this->_cluster->query('SELECT CLEAR_CACHES()');
                }

                $rowCount = 0;

                extract($this->_cluster->profileQuery($query));

                $projections = [];
                $profileData = [];
                $queryEvents = [];

                $q = "SELECT processed_row_count FROM v_monitor.query_profiles WHERE transaction_id = ? AND statement_id = ?";
                $processedRowCount = $this->_cluster->query($q, array($transId, $stmtId))[0]['processed_row_count'];

                $q  = "SELECT * FROM v_monitor.projection_usage pu JOIN v_catalog.projections p ON p.projection_id = pu.projection_id";
                $q .= " WHERE transaction_id = ? AND statement_id = ?";
                $projections = $this->_cluster->query($q, array($transId, $stmtId));

                $q = "SELECT * FROM v_monitor.query_events WHERE transaction_id = ? AND statement_id = ?";
                $queryEvents = $this->_cluster->query($q, array($transId, $stmtId));

                $q = "SELECT
                        SUM(DECODE(counter_name, 'clock time (us)', counter_value, 0))/1000000 running_time,
                        SUM(DECODE(counter_name, 'memory allocated (bytes)', counter_value, 0)) memory_allocated_bytes,
                        SUM(DECODE(counter_name, 'bytes read from disk', counter_value, 0)) read_from_disk_bytes,
                        SUM(DECODE(counter_name, 'bytes read from cache', counter_value, 0)) read_from_cache_bytes,
                        SUM(DECODE(counter_name, 'bytes received', counter_value, 0)) received_bytes,
                        SUM(DECODE(counter_name, 'bytes sent', counter_value, 0)) sent_bytes
                    FROM v_monitor.execution_engine_profiles
                    WHERE transaction_id = ? and statement_id = ?";

                $profileTotals = $this->_cluster->query($q, array($transId, $stmtId))[0];
                $profileTotals['network_bytes'] = $profileTotals['received_bytes'] + $profileTotals['sent_bytes'];

                $planProfile = [];

                $q = "SELECT 
                        eep.path_id,
                        dep.path_line_index,
                        CASE WHEN (COALESCE(dep.path_line_index, 1) = 1) THEN eep.running_time ELSE NULL END AS running_time,
                        CASE WHEN (COALESCE(dep.path_line_index, 1) = 1) THEN eep.memory_allocated_bytes ELSE NULL END AS memory_allocated_bytes,
                        CASE WHEN (COALESCE(dep.path_line_index, 1) = 1) THEN eep.read_from_disk_bytes ELSE NULL END AS read_from_disk_bytes,
                        CASE WHEN (COALESCE(dep.path_line_index, 1) = 1) THEN eep.read_from_cache_bytes ELSE NULL END AS read_from_cache_bytes,
                        CASE WHEN (COALESCE(dep.path_line_index, 1) = 1) THEN eep.received_bytes ELSE NULL END AS received_bytes,
                        CASE WHEN (COALESCE(dep.path_line_index, 1) = 1) THEN eep.sent_bytes ELSE NULL END AS sent_bytes,
                        dep.path_line
                    FROM    
                        (SELECT 
                                transaction_id,
                                statement_id,
                                path_id,
                                SUM(DECODE(counter_name, 'clock time (us)', counter_value, 0)) running_time,
                                SUM(DECODE(counter_name, 'memory allocated (bytes)', counter_value, 0)) memory_allocated_bytes,
                                SUM(DECODE(counter_name, 'bytes read from disk', counter_value, 0)) read_from_disk_bytes,
                                SUM(DECODE(counter_name, 'bytes read from cache', counter_value, 0)) read_from_cache_bytes,
                                SUM(DECODE(counter_name, 'bytes received', counter_value, 0)) received_bytes,
                                SUM(DECODE(counter_name, 'bytes sent', counter_value, 0)) sent_bytes
                            FROM v_internal.dc_execution_engine_profiles 
                            GROUP BY 1,2,3
                        ) eep

                    LEFT JOIN (SELECT transaction_id, statement_id, path_id, path_line_index, path_line FROM v_internal.dc_explain_plans) dep
                    USING (transaction_id, statement_id, path_id)
                    WHERE eep.transaction_id = ? AND eep.statement_id = ?
                    ORDER BY eep.path_id, dep.path_line_index
                ";

                foreach ($this->_cluster->query($q, array($transId, $stmtId)) as $row) {
                    if (isset($planProfile[$row['path_id']])) {
                        $planProfile[$row['path_id']]['path_lines'][] = $row['path_line'];
                    } else {
                        $row['path_lines'] = [$row['path_line']];
                        $row['network_bytes'] = $row['received_bytes'] + $row['sent_bytes'];
                        $row['running_time'] /= 1000000;
                        $planProfile[$row['path_id']] = $row;
                    }
                }

                $this->view->assign(compact(
                    'projections', 'queryEvents', 'profileData', 'profileTotals', 'processedRowCount',
                    'transId', 'stmtId', 'planProfile'
                ));
            } else {
                $explanation = $this->_cluster->explainQuery($query);
                $pattern = '/^\-{30}\s*\nQUERY PLAN DESCRIPTION:\s*\n\-{30}\s*\n(.+?)\n\-{30}\s*\n\-{30,}\s*\nPLAN: BASE QUERY PLAN \(GraphViz Format\)\s*\n\-{30,}\s*\n(.+)/smi';

                preg_match($pattern, $explanation, $matches);

                $tempDir = sys_get_temp_dir();
                $dotInputFile = tempnam($tempDir, 'dotInputFile');
                $dotOutputFile = tempnam($tempDir, 'dotOutputFile');

                $rep = "digraph G {\nnode [fontsize=10];\nedge [fontsize=10];\ngraph [fontsize=10, rankdir";
                $dotGraph = preg_replace('/digraph G \{\ngraph \[rankdir/', $rep, $matches[2]);
                file_put_contents($dotInputFile, $dotGraph);
                shell_exec("cat {$dotInputFile} | dot -Tpng > {$dotOutputFile}");
                unlink($dotInputFile);
                $dotFile = basename($dotOutputFile);
                $explanation = $matches[1];

                $this->view->assign(compact('explanation', 'dotFile'));
            }
        }
    }

    public function dotFileAction() {
        $name = $this->_getParam('name');
        $path = sys_get_temp_dir() . '/' . $name;

        if (!preg_match('/^[a-z0-9]+$/i', $name) || !is_file($path)) {
            die('Invalid filename');
        }

        header('Content-type: image/png');
        header('Content-length: ' . filesize($path));

        echo file_get_contents($path);
        unlink($path);
        die();
    }
}
