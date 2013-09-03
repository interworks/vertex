<?php
/**
 *  o      |                              |         
 *  .,---.-|-- ,---.,---..     .,---.,---.|,--,---.
 *  ||   | |   |---'|    |  |  || o ||    |   `---.
 *  ``   ' `-- `--  `    `--'--'`---'`    '`--`---'
 * 
 * @copyright   Copyright 2011, InterWorks, Inc.
 * @license     Proprietary/Closed
 * @author      Josh Varner <josh.varner@interworks.com>
 */

namespace app\forms;

use vox\form\decorator;
use vox\form\element;

class Login extends \vox\form\Form {
    public function init() {
        $this->setElementDecorators(array(
            'ViewHelper',
            array('Description', array('tag' => 'div', 'class' => 'formHint')),
            array('HtmlTag', array('tag' => 'div', 'class' => 'formInputItem')),
            array('Label'),
            new decorator\Wrapper(),
        ));

        $this->addElements(array(
            array('text', 'login', array(
                'label'     => 'E-mail',
                'required'  => true,
                'autofocus' => 'autofocus',
            )),
            array('password', 'password', array(
                'label'    => 'Password',
                'required' => true,
            )),
            array('button', 'loginBtn', array(
                'type'       => 'submit',
                'label'      => 'Log In',
                'class'      => 'vox-button',
                'decorators' => array('ViewHelper'),
            )),
        ));

        $this->setDecorators(array(
            'FormElements',
            array('Fieldset', array('id' => 'login-form')),
            'Form'
        ));
    }    
}