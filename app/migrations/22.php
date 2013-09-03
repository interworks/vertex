<?php

namespace app\migrations;


class Migrate_22 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Add chapter status tracking as comments \n";
        $db->executeUpdate('CREATE TABLE chapter_comments (id INT AUTO_INCREMENT NOT NULL, author_id INT DEFAULT NULL, chapter_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, comment LONGTEXT NOT NULL, INDEX IDX_B987FB2FF675F31B (author_id), INDEX IDX_B987FB2F579F4768 (chapter_id), PRIMARY KEY(id)) ENGINE = InnoDB');
        $db->executeUpdate('ALTER TABLE chapter_comments ADD CONSTRAINT FK_B987FB2FF675F31B FOREIGN KEY (author_id) REFERENCES members (id)');
         $db->executeUpdate('ALTER TABLE chapter_comments ADD CONSTRAINT FK_B987FB2F579F4768 FOREIGN KEY (chapter_id) REFERENCES chapters (id)');
    }

    protected function _revert($db) {
        echo "Drop table chapter_comments \n";
        $db->executeUpdate("DROP TABLE chapter_comments");

    }
}
