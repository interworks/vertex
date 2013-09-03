<?php

namespace app\migrations;

class Migrate_8 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Adds deadlineStatus,chapterStatus, and year from submissions table \n";
        $db->executeUpdate('ALTER TABLE submissions ADD deadlineStatus VARCHAR(255) NOT NULL, ADD chapterStatus VARCHAR(255) NOT NULL, ADD `year` INT NOT NULL');
    }
    
    protected function _revert($db) {
        echo "Removes deadlineStatus,chapterStatus, and year from submissions table \n";
        $db->executeUpdate('ALTER TABLE submissions DROP COLUMN deadlineStatus');
        $db->executeUpdate('ALTER TABLE submissions DROP COLUMN chapterStatus');
        $db->executeUpdate('ALTER TABLE submissions DROP COLUMN `year`');
    }
}
