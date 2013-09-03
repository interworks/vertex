<?php

namespace app\controllers;

use app\extensions\security\Auth;
use vox\core\Environment;

class ErrorController extends ControllerAbstract {
    public function errorAction() {
        $error = $this->_getParam('error_handler');

        if (Environment::isCli()) {
            $code = $error->exception->getCode();
            
            if (('EXCEPTION_OTHER' === $error->type && 404 === $code)
                || ('EXCEPTION_NO_CONTROLLER' === $error->type)
                || ('EXCEPTION_NO_ACTION' === $error->type)
            ) {
                $message = 'Not Found';
            } else if (403 === $code) {
                $message = 'Forbidden';
            } else {
                $message = 'Application Error';
            }

            echo $message . "\n\n";

            echo get_class($error->exception) . "\n\n";
            echo 'Message: ' . $error->exception->getMessage() . "\n\n";
            echo $error->exception->getTraceAsString() . "\n\n";
            die();
        }

        if (empty($error)) {
            $this->view->message = 'Unknown error';
        } else {
            $code = $error->exception->getCode();
            
            if (('EXCEPTION_OTHER' === $error->type && 404 === $code)
                || ('EXCEPTION_NO_CONTROLLER' === $error->type)
                || ('EXCEPTION_NO_ACTION' === $error->type)
            ) {
                $this->getResponse()->setHttpResponseCode(404);
                $this->render('not-found');
            } else if (403 === $code) {
                $this->getResponse()->setHttpResponseCode($code);
                $this->view->message = 'Forbidden';
            } else {
                error_log($this->_request->getHttpHost() . $this->_request->getRequestUri() . ' - ' . $error->exception->getMessage());
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->message = 'Application error';
            }

            if (in_array(APPLICATION_ENV, array('localdevelopment', 'development'))) {
                $this->view->assign(array(
                    'exception' => $error->exception,
                    'request'   => $error->request,
                ));
            }
        }
    }
}

