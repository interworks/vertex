<?php

namespace app\migrations;

class Migrate_12 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Create Affiliates table for Local Alumni Association, and adds LAA relationship to memberships \n";
        $db->executeUpdate('CREATE TABLE affiliates (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, status INT NOT NULL, PRIMARY KEY(id)) ENGINE = InnoDB');
        $db->executeUpdate('ALTER TABLE memberships ADD affiliate_id INT DEFAULT NULL');
        $db->executeUpdate('ALTER TABLE memberships ADD CONSTRAINT FK_865A47769F12C49A FOREIGN KEY (affiliate_id) REFERENCES affiliates (id)');
        $db->executeUpdate('CREATE INDEX IDX_865A47769F12C49A ON memberships (affiliate_id)');
    }
    
    protected function _revert($db) {
        echo "Drops Affiliates table and removes relationships from Memberships table. \n";
        $db->executeUpdate('ALTER TABLE memberships DROP FOREIGN KEY FK_865A47769F12C49A');
        $db->executeUpdate('DROP TABLE affiliates');
        $db->executeUpdate('ALTER TABLE memberships DROP COLUMN affiliate_id');
    }
}
