<?php

namespace app\migrations;

class Migrate_5 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Drops index on members (status,lastName,firstName,middleName,id); And adds podiumSubscription column on members\n";
        $db->executeUpdate('DROP INDEX IDX_45A0D2FF7B00651C91161A882392A156EDBA2FC6BF396750 ON members');
		$db->executeUpdate('ALTER TABLE members ADD podiumSubscription VARCHAR(50) NOT NULL');
    }
    
    protected function _revert($db) {
        echo "Adds index on members (status,lastName,firstName,middleName,id); And drops podiumSubscription column om members)\n";
        $db->executeUpdate('ALTER TABLE members DROP COLUMN podiumSubscription');
        $db->executeUpdate('CREATE INDEX IDX_45A0D2FF7B00651C91161A882392A156EDBA2FC6BF396750 ON members (status,lastName,firstName,middleName,id)');

    }
}