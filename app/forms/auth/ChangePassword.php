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

namespace app\forms\auth;

use vox\core\Loader;
use vox\form\Form;
use vox\form\SubForm;
use vox\form\decorator;
use vox\form\element;
use app\models\Member;

class ChangePassword extends \vox\form\Form {
    public function init() {
        $opts = array(
            'elementDecorators' => array(
                'ViewHelper',
                array('Description', array('tag' => 'div', 'class' => 'formHint')),
                array('HtmlTag', array('tag' => 'div', 'class' => 'formInputItem')),
                array('Errors', array('placement' => 'append')),
                array('Label'),
                new decorator\Wrapper(),
            ),
        );

        $this->addSubForms(array(
            'details' => new SubForm($opts),
        ));        
        
        $this->details->addElements(array(
            array('password', 'password', array(
                'label' => 'Current Password',
            )),
            array('password', 'newPassword', array(
                'label' => 'New Password',
            )),
            array('password', 'newPasswordVerify', array(
                'label' => 'New Password (Verify)',
            )),
        ));

        if ($minLength = Member::getMinPasswordLength()) {
            $this->details->newPassword->addValidator('StringLength', false, array(
                'min' => $minLength,
                'messages' => array(
                    'stringLengthTooShort' => 'Password must contain at least %min% digits',
                ),
            ));
        }

        $this->details->setRequiredElements(array(
            'password', 'newPassword', 'newPasswordVerify',
        ));
        
        $this->addElements(array(
            new element\Link('cancelBtn', array(
                'label' => 'Cancel',
                'href'  => '/',
            )),
            array('button', 'submitBtn', array(
                'type'       => 'submit',
                'label'      => 'Change Password',
                'decorators' => array('ViewHelper'),
                'ignore'     => true,
            )),
        ));
        
        $this->setOptions(array(
            'name'                   => 'change-password',
            'displayGroupDecorators' => array('FormElements', 'Fieldset'),
            'decorators'             => array('FormElements', 'Form'),
        ));
    }
    
    public function isValid($values, $user = null) {
        $ret = parent::isValid($values);
        
        if ($ret) {
            $currentPass = $this->details->password->getValue();
            $newPass = trim($this->details->newPassword->getValue());
            $newPassVerify = $this->details->newPasswordVerify->getValue();
            
            if (0 !== strcasecmp($newPass, $newPassVerify)) {
                $this->details->newPassword->addError('Passwords must match');
                $this->details->newPasswordVerify->addError('Passwords must match');
                return false;
            } else if ($currentPass === $newPass) {
                $this->details->newPassword->addError('Password must be different');
                return false;
            } else if (!$user || !$user->authenticate($currentPass)) {
                $this->details->password->addError('Password incorrect');
                return false;
            }
        }
        
        return $ret;
    }
}