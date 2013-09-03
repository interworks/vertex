<?php

namespace app\migrations;

class Migrate_7 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Adds life membership number to memberships table \n";
        $db->executeUpdate('ALTER TABLE memberships ADD lifeNumber VARCHAR(255) NOT NULL;');
    }
    
    protected function _revert($db) {
        echo "Removes life membership number from memberships table\n";
        $db->executeUpdate('ALTER TABLE memberships DROP COLUMN lifeNumber');
    }
}
