<?php

namespace app\migrations;

use app\models\contribution\Type;
use app\models\Organization;

class Migrate_16 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Remove null constraint from contributions.notes\n";
        $db->executeUpdate('ALTER TABLE contributions CHANGE notes notes LONGTEXT DEFAULT NULL');
    }

    protected function _revert($db) {
        echo "Add null constraint to contributions.notes\n";
        $db->executeUpdate("ALTER TABLE contributions CHANGE notes notes LONGTEXT NOT NULL");
    }
}
