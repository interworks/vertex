<?php

namespace app\controllers;

use app\extensions\security\Auth;
use vox\core\Environment;
use vox\core\Registry;
use vox\storage\Cache;
use Zend_Session;
use Zend_Session_Namespace;
use Zend_Navigation;
use Zend_View_Helper_Navigation_HelperAbstract;

/**
 * Common controller functionality for the entire app
 *
 * @package app
 * @subpackage controllers
 */
abstract class ControllerAbstract extends \vox\controller\Action {
    protected $_acl = null;
    protected $_auth = null;
    protected $_session = null;
    protected $_user = null;

    /**
     * Array of routes that do not require authentication or `true` to allow all
     */
    protected $_authBypassRoutes = array(
        'default.auth.logout',
        'default.auth.login',
        'default.auth.reset-password',
        'default.admin.status',
        'default.admin.clear-cache',
        'default.feedback.index',
        'default.feedback.submit',
    );

    protected $_changePasswordRoute = 'default.auth.change-password';

    protected $_bypassExpiredRoutes = array(
        'default.auth.change-password',
        'default.auth.logout',
    );

    protected $_loginRoute = 'default.auth.login';
    protected $_loginRouteName = 'auth.login';

    public function init() {
        if (Zend_Session::sessionExists()) {
            $this->_setupSessionAuth();
        }
    }

    protected function _setupSessionAuth() {
        $this->_session = new Zend_Session_Namespace(Registry::get('session')->namespace);
        $this->_auth = $this->_getAuthInstance();
    }

    protected function _checkPages(array &$pages) {
        foreach ($pages as &$page) {
            if (isset($page['permission']) && !Auth::isAllowed($page['permission'], false)) {
                $page['visible'] = false;
            }
            
            if (isset($page['pages']) && is_array($page['pages']) && count($page['pages'])) {
                $this->_checkPages($page['pages']);
            }
        }
    }

    protected function _initNavigation() {
        $pages = array(
            array(
                'label' => 'Status',
                'route' => 'home',
                'id'    => 'home',
                'order' => -100,
            ),
            array(
                'label'      => 'Profiler',
                'route'      => 'default',
                'id'         => 'profiler',
                'controller' => 'profiler',
                'action'     => 'index',
            ),
            array(
                'label'      => 'Administration',
                'route'      => 'default',
                'id'         => 'admin',
                'controller' => 'admin',
                'action'     => 'index',
            ),
            array(
                'label'      => 'Database Designer',
                'route'      => 'default',
                'id'         => 'dbd',
                'controller' => 'dbd',
                'action'     => 'index',
            ),
            array(
                'label'      => 'Objects',
                'route'      => 'default',
                'id'         => 'objects',
                'controller' => 'object',
                'action'     => 'index',
            ),
            array(
                'label'      => 'Diagnostics',
                'route'      => 'default',
                'id'         => 'diagnostics',
                'controller' => 'diagnostics',
                'action'     => 'index',
            ),
        );

        // $this->_checkPages($pages);
        $this->view->navigation(new Zend_Navigation($pages));
    }

    protected function _getAuthInstance() {
        return Auth::getInstance();
    }

    protected function _allowAuthBypass() {
        if (true === $this->_authBypassRoutes) {
            return true;
        }

        return $this->_routeMatches($this->_authBypassRoutes, true);
    }

    /**
     * Check if an array of parameters matches the currently-selected module/controller/action
     *
     * Can check just one set of parameters or an array of parameters
     *
     * @param $matchParams array Array of parameters to match or an array of arrays
     * @param $arrayOfRoutes bool Set to `true` if passing an array of arrays
     * @return bool Returns `true` if a match is found, `false` otherwise
     */
    protected function _routeMatches($matchParams, $arrayOfRoutes = false) {
        $params = array(
            'module'     => $this->request->getModuleName(),
            'controller' => $this->request->getControllerName(),
            'action'     => $this->request->getActionName(),
        );

        $routes = $arrayOfRoutes ? $matchParams : array($matchParams);

        foreach ((array) $routes as $matchParams) {
            $matchParams = $this->_filterRoute($matchParams);

            // Only test the keys mentioned in $matchParams
            $testParams = array_intersect_key($params, $matchParams);
            $matchParams = array_intersect_key($matchParams, $testParams);

            // If there's no difference found, then we have a match
            $diff = array_diff_assoc($testParams, $matchParams);

            if (empty($diff)) {
                return true;
            }
        }

        return false;
    }

    protected function _filterRoute($route) {
        if (!is_array($route)) {
            $parts = array_filter(explode('.', $route));

            if (count($parts) <= 1) {
                throw new Exception('Invalid match parameters');
            }

            foreach ($parts as &$part) {
                if ('*' === $part) {
                    $part = false;
                }
            }

            $route = array();

            if (3 === count($parts)) {
                $route['module'] = array_shift($parts);
            }

            if (2 === count($parts)) {
                $route['controller'] = array_shift($parts);
                $route['action'] = array_shift($parts);
            } else {
                $route['controller'] = array_shift($parts);
            }

            $route = array_filter($route);
        }

        return $route;
    }

    public function postDispatch() {
        $ret = parent::postDispatch();

        if ($this->hasSessionMessages()) {
            $this->view->hasSessionMessages = true;
            $this->view->sessionMessageController = $this;
        }

        return $ret;
    }

    public function addSessionMessage($message) {
        if ($this->_session) {
            if (!isset($this->_session->messages)) {
                $this->_session->messages = array();
            }

            $this->_session->messages[] = $message;
        }
        
        return $this;
    }
    
    public function hasSessionMessages() {
        return !!($this->_session && !empty($this->_session->messages));
    }
    
    public function getSessionMessages($clear = true) {
        if (!$this->_session || empty($this->_session->messages)) {
            return array();
        }

        $ret = $this->_session->messages;
        
        if ($clear) {
            $this->_session->messages = array();
        }
        
        return $ret;
    }

    public function preDispatch() {
        parent::preDispatch();

        if (Environment::isCli()) {
            $this->_helper->viewRenderer->setNoRender();
            $this->_helper->layout->disableLayout();
            return;
        }

        if ('error' === $this->request->getControllerName()) {
            return;
        }

        $this->_initNavigation();

        return;

        $user = false;

        if ($this->_auth && $this->_auth->hasIdentity()) {
            $user = $this->_auth->getIdentity();

            if ($user && 0 != $user->getStatus()) {
                $user = false;
                $this->_auth->clearIdentity();
                Zend_Session::destroy();
                return $this->redirector->gotoRouteAndExit(array(), 'auth.login', true);
            }
        }

        if (!$user && !$this->_allowAuthBypass()) {
            if (!$this->_routeMatches($this->_loginRoute)) {
                $loginRoute = $this->_filterRoute($this->_loginRoute);

                if ($this->request->isXmlHttpRequest()) {
                    $this->json(array(
                        'result'   => 'error',
                        'reason'   => 'logged-out',
                        'loginUrl' => $this->url->url($loginRoute, $this->_loginRouteName, true),
                    ));
                }
                
                if ($this->_routeMatches(array('default.index.index'))) {
                    return $this->_forward('login', 'auth');
                } else if (!$this->request->isPost()) {
                    if (!$this->_session) {
                        $tihs->_setupSessionAuth();
                    }
                    $this->_session->loginReturnUrl = $this->request->getRequestUri();
                }

                return $this->redirector->gotoRouteAndExit($loginRoute, $this->_loginRouteName, true);
            }
        }

        if ($user) {
            $this->view->currentUser = $this->_user = $user;

            if ($user->isPasswordExpired() && !$this->_allowAuthBypass()) {
                $route = $this->_filterRoute($this->_changePasswordRoute);
                return $this->redirector->gotoRouteAndExit($route, 'default', true);
            }
        }
        
    }
}