<?php

namespace app\migrations;

use app\models\contribution\Type;
use app\models\Organization;

class Migrate_15 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Add contributions and contribution_types tables\n";
        $db->executeUpdate('CREATE TABLE contributions (id INT AUTO_INCREMENT NOT NULL, member_id INT DEFAULT NULL, chapter_id INT DEFAULT NULL, type_id INT DEFAULT NULL, date DATETIME NOT NULL, amount NUMERIC(14, 2) NOT NULL, notes LONGTEXT NOT NULL, INDEX IDX_76391EFE7597D3FE (member_id), INDEX IDX_76391EFE579F4768 (chapter_id), INDEX IDX_76391EFEC54C8C93 (type_id), PRIMARY KEY(id)) ENGINE = InnoDB');
        $db->executeUpdate('CREATE TABLE contribution_types (id INT AUTO_INCREMENT NOT NULL, organization_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_3C43828232C8A3DE (organization_id), PRIMARY KEY(id)) ENGINE = InnoDB');
        $db->executeUpdate('ALTER TABLE contributions ADD CONSTRAINT FK_76391EFE7597D3FE FOREIGN KEY (member_id) REFERENCES members (id)');
        $db->executeUpdate('ALTER TABLE contributions ADD CONSTRAINT FK_76391EFE579F4768 FOREIGN KEY (chapter_id) REFERENCES chapters (id)');
        $db->executeUpdate('ALTER TABLE contributions ADD CONSTRAINT FK_76391EFEC54C8C93 FOREIGN KEY (type_id) REFERENCES contribution_types (id)');
        $db->executeUpdate('ALTER TABLE contribution_types ADD CONSTRAINT FK_3C43828232C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id)');
        
        $em = $this->getEm();
        
        $kky = Organization::findOneByName('KKY');
        $tbs = Organization::findOneByName('TBS');
        $naa = Organization::findOneByName('NAA');

        $types = array(
            array('KKY AA Programs', $kky),
            array('Archives', $kky),
            array('All Aboard', $kky),
            array('Trust', $kky),
            array('SOS', $kky),
            array('General Fund', $kky),
            array('NAA Programs', $naa),
            array('Endowment', $naa),
            array('Tributes', $tbs),
            array('General Fund - Database/Roster', $tbs),
            array('ROR', $tbs),
            array('Archives', $tbs),
            array('TBS AA Programs', $tbs),
            array('General Fund', $tbs),
            array('SOS', $tbs),
            array('Trust', $tbs),
            array('ReMember (2011)', $tbs),
        );
        
        foreach ($types as $arr) {
            $obj = new Type();
            $obj->setName($arr[0]);
            $obj->setOrganization($arr[1]);
            $em->persist($obj);
        }
        
        $em->flush();
    }

    protected function _revert($db) {
        echo "Drop contributions and contribution_types tables\n";
        $db->executeUpdate("DROP TABLE contributions");
        $db->executeUpdate("DROP TABLE contribution_types");
    }
}
