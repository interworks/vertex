<?php

namespace app\migrations;


class Migrate_19 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Change columns colonyAdvisingPerson -> colonyAdvisingPersonEmail and advisingPerson -> colonyAdvisingPersonName \n";
        $db->executeUpdate('ALTER TABLE chapters ADD colonyAdvisingPersonName VARCHAR(255) NOT NULL, ADD colonyAdvisingPersonEmail VARCHAR(255) NOT NULL, DROP advisingPerson, DROP colonyAdvisingPerson');
    }

    protected function _revert($db) {
        echo "Restore columns colonyAdvisingPerson and advisingPerson \n";
        $db->executeUpdate("ALTER TABLE DROP colonyAdvisingPersonName");
        $db->executeUpdate("ALTER TABLE DROP colonyAdvisingPersonEmail");
        $db->executeUpdate('ALTER TABLE chapters ADD advisingPerson VARCHAR(255) NOT NULL');
        $db->executeUpdate('ALTER TABLE chapters ADD colonyAdvisingPerson VARCHAR(255) NOT NULL');
    }
}
