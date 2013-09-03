<?php
/**
 * o      |                              |         
 * .,---.-|-- ,---.,---..     .,---.,---.|,--,---.
 * ||   | |   |---'|    |  |  || o ||    |   `---.
 * ``   ' `-- `--  `    `--'--'`---'`    '`--`---'
 *
 * @author    Josh Varner <josh.varner@interworks.com>
 */

namespace app\views\helpers;

/**
 * Returns a stylized version of the organization name
 */
class OrgTag extends \Zend_View_Helper_Abstract {
    public function __invoke($organization) {
        $org = strtolower(trim($organization));

        if ('kky' === $org) {
            $org = '&Kappa;&Kappa;&Psi;';
        } else if ('tbs' === $org) {
            $org = '&Tau;&Beta;&Sigma;';
        } else {
            return '';
        }
        
        return "<span class=\"organization-tag\"><span class=\"greek\">{$org}</span></span>";
    }
}
