<?php

namespace app\migrations;

class Migrate_1 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Adding members.cachedName\n";
        $db->executeUpdate("ALTER TABLE members ADD cachedName VARCHAR(512) NOT NULL");
        echo "Populating members.cachedName\n";
        $db->executeUpdate("UPDATE members SET cachedName = TRIM(CONCAT(firstName, CONCAT(' ', TRIM(CONCAT(middleName, CONCAT(' ', lastName))))))");
        echo "Indexing cachedName\n";
        $db->executeUpdate("CREATE INDEX cachedName_idx ON members (cachedName)");
    }
    
    protected function _revert($db) {
        echo "Dropping members.cachedName\n";
        $db->executeUpdate("ALTER TABLE members DROP cachedName");
    }
}