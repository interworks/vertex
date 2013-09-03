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

class ChangeEmail extends \vox\form\Form {
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
            array('text', 'email', array(
                'label'    => 'Email',
                'required' => true,
                'class'  => array('email'),
            )),
            array('text', 'emailRepeat', array(
                'label'    => 'Repeat Email',
                'required' => true,
                'class'  => array('email'),
            )),
        ));


        $this->addElements(array(
            new element\Link('cancelBtn', array(
                'label' => 'Cancel',
                'href'  => '/',
            )),
            array('button', 'submitBtn', array(
                'type'       => 'submit',
                'label'      => 'Change Email',
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
            $email = trim($this->details->email->getValue());
            $emailRepeat = trim($this->details->emailRepeat->getValue());
                        
            if (0 !== strcasecmp($email, $emailRepeat)) {
                $this->details->email->addError('Emails must match');
                $this->details->emailRepeat->addError('Emails must match');
                return false;
            }
        }
        
        return $ret;
    }
}