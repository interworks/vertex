<?php

namespace app\migrations;

class Migrate_14 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Drop extra sponsor and DOB columns\n";
        $db->executeUpdate('ALTER TABLE chapters DROP sponsorName, DROP sponsorEmail, DROP dobName, DROP dobEmail');
    }
    
    protected function _revert($db) {
        echo "Add extra sponsor and DOB columns\n";
        $db->executeUpdate("ALTER TABLE chapters ADD sponsorName VARCHAR(255) NOT NULL DEFAULT ''");
        $db->executeUpdate("ALTER TABLE chapters ADD sponsorEmail VARCHAR(255) NOT NULL DEFAULT ''");
        $db->executeUpdate("ALTER TABLE chapters ADD dobName VARCHAR(255) NOT NULL DEFAULT ''");
        $db->executeUpdate("ALTER TABLE chapters ADD dobEmail VARCHAR(255) NOT NULL DEFAULT ''");
    }
}
