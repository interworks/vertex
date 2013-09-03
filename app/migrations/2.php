<?php

namespace app\migrations;

class Migrate_2 extends \vox\data\Migration {
    protected function _apply($db) {
        echo "Adding to chapters: country, physCountry, sponsorCountry, dobCountry\n";
        $db->executeUpdate('ALTER TABLE chapters ' . implode(', ', array(
            'ADD country VARCHAR(255) NOT NULL',
            'ADD physCountry VARCHAR(255) NOT NULL',
            'ADD sponsorCountry VARCHAR(255) NOT NULL',
            'ADD dobCountry VARCHAR(255) NOT NULL',
        )));

        echo "Adding to members: country, permCountry, transferNotes\n";
        $db->executeUpdate('ALTER TABLE members ' . implode(', ', array(
            'ADD country VARCHAR(255) NOT NULL',
            'ADD permCountry VARCHAR(255) NOT NULL',
            'ADD transferNotes LONGTEXT NOT NULL',
        )));
    }
    
    protected function _revert($db) {
        echo "Dropping from chapters: country, physCountry, sponsorCountry, dobCountry\n";
        $db->executeUpdate('ALTER TABLE chapters ' . implode(', ', array(
            'DROP country',
            'DROP physCountry',
            'DROP sponsorCountry',
            'DROP dobCountry',
        )));

        echo "Dropping from members: country, permCountry, transferNotes\n";
        $db->executeUpdate('ALTER TABLE members ' . implode(', ', array(
            'DROP country',
            'DROP permCountry',
            'DROP transferNotes',
        )));
    }
}