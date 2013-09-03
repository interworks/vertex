<?php

namespace app\migrations;

class Migrate_9 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Adds completedDate to submissions table \n";
        $db->executeUpdate('ALTER TABLE submissions ADD completedDate DATE DEFAULT NULL');
    }
    
    protected function _revert($db) {
        echo "Removes completedDate from submissions table \n";
        $db->executeUpdate('ALTER TABLE submissions DROP COLUMN completedDate');
    }
}
