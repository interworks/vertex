<?php

namespace app\migrations;

use app\models\contribution\Type;
use app\models\Organization;

class Migrate_17 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Add initiations.honorary column\n";
        $db->executeUpdate('ALTER TABLE initiations ADD honorary TINYINT(1) NOT NULL');
    }

    protected function _revert($db) {
        echo "Drop initiations.honorary column\n";
        $db->executeUpdate("ALTER TABLE initiations DROP honorary");
    }
}
