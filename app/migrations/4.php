<?php

namespace app\migrations;

class Migrate_4 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Remove Foreign Key on submissions table, remove index on submissions (status)\n";
		$db->executeUpdate('ALTER TABLE submissions DROP FOREIGN KEY FK_3F6169F77B00651C');
		$db->executeUpdate('DROP INDEX IDX_3F6169F77B00651C ON submissions');
		$db->executeUpdate('TRUNCATE TABLE submission_statuses');
		$db->executeUpdate("INSERT INTO submission_statuses
							(name, weight)
							VALUES
							('Pending Submission', 1),
							('Submit for Sponsor Approval', 2),
							('Requires Corrections', 3),
							('Sponsor Approved, Pending Collection of Fees', 4),
							('Sponsor Approved', 5),
							('Form and Payment sent to National HQ', 6),
							('Transaction Confirmed', 7),
							('Transaction Completed', 8),
							('Draft', 9),
							('draft_discarded',10),
							('', 11)");

    }
    
    protected function _revert($db) {
        echo "Add Foreign Key on submissions table, add index on submissions (status)\n";
        $db->executeUpdate('ALTER TABLE submissions ADD CONSTRAINT FK_3F6169F77B00651C FOREIGN KEY (status) REFERENCES submission_statuses(name)');
        $db->executeUpdate('CREATE INDEX IDX_3F6169F77B00651C ON submissions (status);');
        

    }
}