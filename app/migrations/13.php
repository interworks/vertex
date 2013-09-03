<?php

namespace app\migrations;

class Migrate_13 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Create initiations table\n";
        $db->executeUpdate('CREATE TABLE initiations (id INT AUTO_INCREMENT NOT NULL, member_id INT DEFAULT NULL, chapter_id INT DEFAULT NULL, initiationNumber INT NOT NULL, initiationDate DATE DEFAULT NULL, INDEX IDX_B1E944FA7597D3FE (member_id), INDEX IDX_B1E944FA579F4768 (chapter_id), PRIMARY KEY(id)) ENGINE = InnoDB');
        $db->executeUpdate('ALTER TABLE initiations ADD CONSTRAINT fk_members FOREIGN KEY (member_id) REFERENCES members (id)');
        $db->executeUpdate('ALTER TABLE initiations ADD CONSTRAINT FK_chapters FOREIGN KEY (chapter_id) REFERENCES chapters (id)');
    }
    
    protected function _revert($db) {
        echo "Drops initiations table\n";
        $db->executeUpdate('ALTER TABLE initiations DROP FOREIGN KEY FK_chapters');
        $db->executeUpdate('ALTER TABLE initiations DROP FOREIGN KEY fk_members');
        $db->executeUpdate('DROP TABLE initiations');
    }
}
