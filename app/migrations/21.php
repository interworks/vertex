<?php

namespace app\migrations;


class Migrate_21 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Add recolonizationDate to chapter table \n";
        $db->executeUpdate('ALTER TABLE chapters ADD recolonizationDate DATE DEFAULT NULL');
    }

    protected function _revert($db) {
        echo "Remove recolonizationDate from chapters table \n";
        $db->executeUpdate("ALTER TABLE chapters DROP recolonizationDate");

    }
}
