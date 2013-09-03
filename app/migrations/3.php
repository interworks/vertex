<?php

namespace app\migrations;

class Migrate_3 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Creating submission_statuses table\n";
        $db->executeUpdate('CREATE TABLE submission_statuses (name VARCHAR(255) NOT NULL, weight INT NOT NULL, PRIMARY KEY(name)) ENGINE = InnoDB');

        echo "Inserting data for submission_statues\n";
        $db->executeUpdate('REPLACE INTO submission_statuses (name, weight) SELECT status, 100 FROM submissions GROUP BY status');
        $db->executeUpdate("REPLACE INTO submission_statuses (name, weight) VALUES 
                            ('Pending Submission',1),
                            ('Submit for Sponsor Approval',2),
                            ('Requires Corrections',3),
                            ('Sponsor Approved, Pending Collection of Fees',4),
                            ('Sponsor Approved',5),
                            ('Form and Payment sent to National HQ',6),
                            ('Transaction Confirmed',7),
                            ('Transaction Completed',8),
                            ('draft_discarded', 9),
                            ('',10)
                            ");
        $db->executeUpdate('ALTER TABLE submissions ADD CONSTRAINT FK_3F6169F77B00651C FOREIGN KEY (status) REFERENCES submission_statuses(name)');
        $db->executeUpdate('CREATE INDEX IDX_3F6169F77B00651C ON submissions (status);');
    }

    protected function _revert($db) {
        echo "Dropping submission_statuses table\n";
        $db->executeUpdate('ALTER TABLE submissions DROP FOREIGN KEY FK_3F6169F77B00651C');
        $db->executeUpdate('DROP TABLE submission_statuses');
        $db->executeUpdate('DROP INDEX IDX_3F6169F77B00651C ON submissions');
    }
}