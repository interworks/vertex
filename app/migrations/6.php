<?php

namespace app\migrations;

class Migrate_6 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Adds status column to districts table, and adds additional columns to submissions table \n";
        $db->executeUpdate('ALTER TABLE districts ADD status INT NOT NULL;');
		$db->executeUpdate('ALTER TABLE submissions ADD postmarkDate DATE, ADD receiptDate DATE, ADD initiationDate DATE, ADD activesPaid INT NOT NULL, ADD conditionalPaid INT NOT NULL, ADD numberOfDuesProcessed INT NOT NULL, ADD initiatesPaid INT NOT NULL, ADD cf VARCHAR(255) NOT NULL');
    }
    
    protected function _revert($db) {
        echo "Removes status column from districts table, and removes additional columns to submissions table\n";
        $db->executeUpdate('ALTER TABLE districts DROP COLUMN status');
        $db->executeUpdate('ALTER TABLE submissions DROP COLUMN postmarkDate, DROP COLUMN receiptDate, DROP COLUMN initiationDate, DROP COLUMN activesPaid, DROP COLUMN conditionalPaid, DROP COLUMN numberOfDuesProcessed, DROP COLUMN initiatesPaid, DROP COLUMN cf');

    }
}
