<?php

namespace app\migrations;

class Migrate_11 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Adds greekLetters and dropDate to chapters table. Add charter to memberships table. \n";
        $db->executeUpdate('ALTER TABLE chapters ADD greekLetters VARCHAR(255) NOT NULL, ADD dropDate DATE DEFAULT NULL');
        $db->executeUpdate('ALTER TABLE memberships ADD charter VARCHAR(255) NOT NULL');
    }
    
    protected function _revert($db) {
        echo "Removes greekLetters and dropDate from chapters table. Removes charter to memberships table \n";
        $db->executeUpdate('ALTER TABLE chapters DROP COLUMN greekLetters');
        $db->executeUpdate('ALTER TABLE chapters DROP COLUMN dropDate');
        $db->executeUpdate('ALTER TABLE memberships DROP COLUMN charter');
    }
}
