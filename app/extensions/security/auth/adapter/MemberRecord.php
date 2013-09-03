<?php

namespace app\extensions\security\auth\adapter;

use app\models\Member;
use Zend_Auth_Result;

class MemberRecord implements \Zend_Auth_Adapter_Interface {
    protected $_identity = null;
    protected $_credential = null;

    public function setIdentity($value) {
        $this->_identity = $value;
    }

    public function setCredential($value) {
        $this->_credential = $value;
    }

    public function authenticate() {
        if (strlen($this->_identity) && strlen($this->_credential)) {
            $record = $this->_findIdentityRecord();

            if ($record && $record->authenticate($this->_credential)) {
                return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $record);
            }
        }
        
        
        return new Zend_Auth_Result(Zend_Auth_Result::FAILURE, $this->_identity);
    }

    protected function _findIdentityRecord() {
        $member = Member::findBy(array('email' => $this->_identity), array('status' => 'ASC'), 1);

        if (1 === count($member)) {
            return $member[0];
        }

        return false;
    }

    public function loadIdentity($record) {
        return Member::find($record->id);
    }
}
