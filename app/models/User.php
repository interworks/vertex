<?php

namespace app\models;

use app\extensions\security\Role;
use vox\util\String;
use DateTime;
use DateInterval;

/** @MappedSuperClass */
abstract class User extends \vox\data\model\Doctrine {
    /** @column(type="string", length=255) */
    protected $passwordHash = '';
    /** @column(type="string", length=255) */
    protected $passwordResetToken = '';
    /** @column(type="datetime") */
    protected $passwordResetExpireDate;
    /** @column(type="datetime") */
    protected $lastPasswordChange;
    /**
     * @column(type="json")
     */
    protected $roles = array();
    /** @Column(type="json") */
    protected $permissions = array();
    
    protected $_permissions = null;
    protected $_hiddenFields = array('passwordHash', 'passwordResetToken', 'passwordResetExpireDate', 'lastPasswordChange');

    protected static $_minPasswordLength = 6;
    
    public function getMasterRole() {
        return 'user_' . $this->id;
    }

    public function addRole($name) {
        $old = $this->roles;
        
        if (!is_array($this->roles)) {
            $this->roles = array($name);
        } else if (!in_array($name, $this->roles)) {
            $this->roles[] = $name;
        }
        
        $this->_onPropertyChanged('roles', $old, $this->roles);
        return $this;
    }
    
    public function setRoles(array $roles = array()) {
        $roles = array_unique(array_filter($roles));
        $this->_onPropertyChanged('roles', $this->roles, $roles);
        $this->roles = $roles;
    }
    
    public function getRoles() {
        return is_array($this->roles) ? $this->roles : array();
    }

    public function getPermissions() {
        if (null === $this->_permissions) {
            $perms = Role::getPermissions($this->getRoles());

            if (is_array($this->permissions)) {
                $perms = array_filter($this->permissions + $perms);
            }

            $this->_permissions = $perms;
        }
        
        return $this->_permissions;
    }
    
    public function isAllowed($perm) {
        foreach ((array) $perm as $key) {
            if (isset($this->_permissions[$key])) {
                return true;
            }
        }
        
        return false;
    }

    public function authenticate($tryPassword) {
        if (!strlen($this->passwordHash)) {
            return $this->_authFail();
        }
        
        if (String::crypt($tryPassword, $this->passwordHash) === $this->passwordHash) {
            return $this->_authSuccess();
        }

        return $this->_authFail();
    }

    public function setPassword($password) {
        $now = new DateTime('now');

        $this->setPasswordHash(String::crypt($password));
        $this->setPasswordResetToken('');
        $this->setPasswordResetExpireDate($now);
        $this->setLastPasswordChange(clone $now);
        
        return $this;
    }

    public static function findOneByResetToken($token) {
        $rec = static::findOneBy(array(
            'passwordResetToken' => $token,
        ));

        if ($rec) {
            $now = new DateTime('now');
            
            if ($now < $rec->passwordResetExpireDate) {
                return $rec;
            }
        }
        
        return $rec;
    }

    public function generateResetToken() {
        $token  = sha1(microtime() . mt_rand() . $this->email);
        $token .= sha1($token . microtime() . mt_rand() . $this->email);
        
        $expires = new DateTime('now');
        $expires->add(new DateInterval('P14D'));

        $this->setPasswordResetToken($token);
        $this->setPasswordResetExpireDate($expires);
        
        return compact('token', 'expires');
    }
    
    public function isPasswordExpired() {
        return false;
    }
    
    public static function getMinPasswordLength() {
        return static::$_minPasswordLength;
    }
    
    /**
     * Wrapper for sub-classes to catch auth failures
     */
    protected function _authFail() {
        return false;
    }

    /**
     * Wrapper for sub-classes to catch auth successes
     */
    protected function _authSuccess() {
        return true;
    }
}