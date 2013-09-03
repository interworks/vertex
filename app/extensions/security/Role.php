<?php
/**
 *  o      |                              |         
 *  .,---.-|-- ,---.,---..     .,---.,---.|,--,---.
 *  ||   | |   |---'|    |  |  || o ||    |   `---.
 *  ``   ' `-- `--  `    `--'--'`---'`    '`--`---'
 * 
 * @copyright   Copyright 2011, InterWorks, Inc.
 * @license     Proprietary/Closed
 * @author      Josh Varner
 */

namespace app\extensions\security;

class Role {
    const ANONYMOUS = 'anonymous';
    const AUTHENTICATED = 'authenticated';
    
    protected static $_roles = array();
    
    public static function __init() {
        $roles = array(
            self::ANONYMOUS => array('access_site'),
            self::AUTHENTICATED => array('session', 'search', 'chapter_search', 'chapter_view'),
            'member' => array('member'),
            'alumnassoc' => array('alumnassoc'),
            'alumnofficer' => array(
                'alumnofficer', 'member_search', 'member_view', 'submission_view_all', 'submission_view', 'member_view_all'
            ),
            'officer' => array(
                'officer', 'member_search', 'member_view', 'submission_view', 'submission_create',
            ),
            'approver' => array(
                'approver', 'submission_view_all'
            ),
            'district' => array(
                'district',
            ),
            'sponsor' => array('sponsor'),
            'dob' => array('dob'),
            'national' => array(
                'national',
                'district',
                'member_view_all',
            ),
            'hq' => array(
                'hq',
                'membership_create',
                'member_create',
                'member_update',
                'member_delete',
                'member_view_all',
                'membership_update',
                'membership_delete',
                'chapter_create',
                'chapter_update',
                'member_delete',
                'shingles',
                'contribution_view',
                'contribution_create',
                'contribution_delete',
                'initiation_create',
                'initiation_delete',
            ),
            'admin' => array('admin', 'cache_clear'),
        );
        
        foreach ($roles as $role => &$perms) {
            $perms = array_fill_keys($perms, true);
        }
        
        unset($perms);

        // Inheritance
        $roles[self::AUTHENTICATED] += $roles[self::ANONYMOUS];
        $roles['member'] += $roles[self::AUTHENTICATED];
        $roles['alumnassoc'] += $roles['member'];
        $roles['alumnofficer'] += $roles['alumnassoc'];
        $roles['officer'] += $roles['member'];
        $roles['approver'] += $roles['officer'];
        $roles['district'] += $roles['approver'];
        $roles['sponsor'] += $roles['approver'];
        $roles['dob'] += $roles['approver'];
        $roles['national'] += ($roles['sponsor'] + $roles['dob']);
        $roles['hq'] += $roles['national'];
        $roles['admin'] += $roles['hq'];
        
        static::$_roles = $roles;
    }
    
    public static function getAll() {
        return static::$_roles;
    }
    
    public static function getPermissions($roles) {
        $perms = array();

        foreach ((array) $roles as $role) {
            if (isset(static::$_roles[$role])) {
                $perms += static::$_roles[$role];
            }
        }
        
        return $perms;
    }
}