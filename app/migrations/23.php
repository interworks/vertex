<?php

namespace app\migrations;


class Migrate_23 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Add a feeData column to submissions to track CPU fees \n";
        $db->executeUpdate("ALTER TABLE submissions ADD feeData LONGTEXT NOT NULL COMMENT '(DC2Type:json)'");
    }

    protected function _revert($db) {
        echo "Drop column feeData from submissions \n";
        $db->executeUpdate("ALTER TABLE submissions DROP feeData");

    }
}
