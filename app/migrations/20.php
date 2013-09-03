<?php

namespace app\migrations;


class Migrate_20 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Add goesBy (nickname) field to members table \n";
        $db->executeUpdate('ALTER TABLE members ADD goesBy VARCHAR(255) NOT NULL');
    }

    protected function _revert($db) {
        echo "Remove goesBy from members table \n";
        $db->executeUpdate("ALTER TABLE members DROP goesBy");

    }
}
