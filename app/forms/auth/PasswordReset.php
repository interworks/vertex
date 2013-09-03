<?php

namespace app\forms\auth;

use vox\Form;
use vox\form\SubForm;
use vox\form\decorator;
use vox\form\element;

use \Zend_Form_Element_Text;
use \Zend_Form_Element_Password;
use \Zend_Form_Element_Select;
use \Zend_Form_Element_Submit;
use \Zend_Form_Element_Hidden;

class PasswordReset extends \vox\form\Form {
    public function init() {
        $decorators = array(
            array('ViewHelper'),
            array('HtmlTag', array('tag' => 'div', 'class' => 'formInputItem')),
            array('Description', array('tag' => 'div', 'class' => 'formHint')),
            array('Label'),
            array(   'decorator' => array('Wrapper' => 'HtmlTag'), 
                'options' => array('tag' => 'div', 'class' => 'formItem')
            ),
            array('Errors'),
        );
        
        $this->addElements(array(
            new Zend_Form_Element_Text('email', array(
                'label' => 'E-mail Address',
                'disabled' => 'disabled'
            )),
            new Zend_Form_Element_Password('password', array('label' => 'New Password')),
            new Zend_Form_Element_Password('passwordVerify', array('label' => 'New Password (Verify)')),
            new Zend_Form_Element_Submit('submit', array(
                'label' => 'Set Password',
                'class' => 'vox-button',
                'decorators' => array(
                    array('decorator' => 'ViewHelper', 'options' => array('class' => 'vox-button'))
                ),
            )),
            new Zend_Form_Element_Hidden('token'),
        ));

        $this->setElementDecorators($decorators, array('submit'), false);
        $this->setDecorators(array('FormElements', 'Fieldset', 'Form'));
    }
}