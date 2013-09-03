<?php

namespace app\extensions\security;

use app\extensions\security\auth\adapter\MemberRecord;

class Auth extends \vox\security\Auth {
    protected static $_instances = array();

    public function getAdapter() {
        if (is_null($this->_adapter)) {
            $this->_adapter = new MemberRecord();
        }
        
        return $this->_adapter;
    }
    
    /**
     * Returns the identity from storage or null if no identity is available
     *
     * @return mixed|null
     */
    public function getIdentity() {
        if (null === $this->_identity) {
            $storage = $this->getStorage();

            if ($storage->isEmpty()) {
                return null;
            }

            $this->_identity = $this->getAdapter()->loadIdentity($storage->read());
        }
        
        return $this->_identity;
    }
    
    public static function isAllowed($perms, $newInstanceIfEmpty = true) {
        $identity = null;

        if ($newInstanceIfEmpty || static::hasInstance()) {
            $identity = static::getInstance()->getIdentity();
        }
        
        if (!$identity) {
            $grants = Role::getPermissions(Role::ANONYMOUS);
        } else {
            $grants = $identity->getPermissions();
        }
        
        foreach ((array) $perms as $perm) {
            if (!isset($grants[$perm])) {
                return false;
            }
        }
        
        return true;
    }
}
