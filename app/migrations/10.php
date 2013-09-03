<?php

namespace app\migrations;

class Migrate_10 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Adds taxId to chapters table \n";
        $db->executeUpdate('ALTER TABLE chapters ADD taxId VARCHAR(255) NOT NULL');
    }
    
    protected function _revert($db) {
        echo "Removes taxId from chapters table \n";
        $db->executeUpdate('ALTER TABLE chapters DROP COLUMN taxId');
    }
}
