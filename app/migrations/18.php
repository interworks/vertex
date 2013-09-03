<?php

namespace app\migrations;

use app\models\contribution\Type;
use app\models\Organization;

class Migrate_18 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Add 'Submitted to Headquarters' submission status\n";
        $db->executeUpdate("INSERT INTO submission_statuses (name, weight) VALUES ('Submitted to Headquarters', 6)");
    }

    protected function _revert($db) {
        echo "Remove 'Submitted to Headquarters' submission status\n";
        $db->executeUpdate("DELETE FROM submission_statuses WHERE name = 'Submitted to Headquarters'");
    }
}
