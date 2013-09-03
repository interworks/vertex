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

class RequestPasswordReset extends \vox\form\Form {
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
            array('text', 'email', array(
                'label'      => 'Email Address',
                'required'   => true,
                'decorators' => $decorators,
            )),
            array('submit', 'submitBtn', array(
                'label' => 'Reset Password',
                'class' => 'vox-button',
                'decorators' => array(
                    array('decorator' => 'ViewHelper', 'options' => array('class' => 'vox-button'))
                ),
            )),
        ));

        $this->setDecorators(array('FormElements', 'Fieldset', 'Form'));
    }
}