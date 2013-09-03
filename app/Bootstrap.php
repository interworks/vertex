<?php

namespace app;

use app\extensions\security\Auth;
use vox\controller\Dispatcher;
use vox\core\Environment;
use vox\core\Registry;
use vox\storage\Cache;
use Zend_Controller_Front;
use Zend_Controller_Router_Rewrite;
use Zend_Controller_Router_Route as Route;
use Zend_Controller_Router_Route_Static as StaticRoute;
use Zend_Controller_Router_Route_Regex as RegexRoute;
use Zend_Mail;
use Zend_Mail_Transport_File;
use Zend_Session;
use Zend_View_Helper_Navigation_HelperAbstract;
use Exception;
use Doctrine\Common\ClassLoader;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use lithium\storage\cache\adapter\Apc;
use vox\core\Loader;
use vox\data\dbal\logging\QueryLogger;
use vox\data\vertica\Cluster;
use Zend_Controller_Request_Simple;
use vox\controller\router\Cli as CliRouter;
use Zend_Controller_Response_Cli;

/**
 * Main application bootstrapper
 */
class Bootstrap extends \vox\application\Bootstrap {
    protected function _initDispatcher() {
        Zend_Controller_Front::getInstance()->setDispatcher(new Dispatcher());
        $this->bootstrap('frontController');
    }

    protected function _initConfig() {
        $opts = $this->getOptions();
        Registry::init($opts['vox']);

        if (Environment::is('localdevelopment')) {
            Zend_Mail::setDefaultTransport(new Zend_Mail_Transport_File());
        }
    }

    protected function _initCluster() {
        $connectionOptions = require __DIR__ . '/config/bootstrap/vertica-config.php';
        $cluster = new Cluster($connectionOptions);
        Registry::getInstance()->cluster = $cluster;
    }

    protected function _initDatabase() {
        Loader::addLibraries(array(
            'Doctrine\Common' => array(
                'bootstrap' => false,
                'path'      => VOX_LIBRARY_PATH . '/doctrine/lib/vendor/doctrine-common/lib/Doctrine/Common',
            ),
            'Doctrine\DBAL' => array(
                'bootstrap' => false,
                'path'      => VOX_LIBRARY_PATH . '/doctrine/lib/vendor/doctrine-dbal/lib/Doctrine/DBAL',
            ),
            'Doctrine\ORM' => array(
                'bootstrap' => false,
                'path'      => VOX_LIBRARY_PATH . '/doctrine/lib/Doctrine/ORM',
            ),
        ));

        if (Environment::isCli()) {
            Loader::addLibraries(array(
                'Symfony\Component\Console' => array(
                    'bootstrap' => false,
                    'path'      => VOX_LIBRARY_PATH . '/doctrine/lib/vendor/Symfony/Component/Console',
                ),
                'Symfony\Component\Yaml' => array(
                    'bootstrap' => false,
                    'path'      => VOX_LIBRARY_PATH . '/doctrine/lib/vendor/Symfony/Component/Yaml',
                ),
            ));
        }

        Type::addType('json', 'vox\data\type\Json');
       
        $opts = Registry::get('data')->doctrine;
        $config = new Configuration;
        $cache = ($opts['enableApcCache'] && Apc::enabled() ? new ApcCache : new ArrayCache);
        $config->setAutoGenerateProxyClasses(!!$opts['autoGenerateProxies']);
        
        $cache->setNamespace('d2_' . APP_CACHE_KEY_PREFIX);
        $config->setMetadataCacheImpl($cache);
        $driverImpl = $config->newDefaultAnnotationDriver(array(APPLICATION_PATH . '/models'));
        $config->setMetadataDriverImpl($driverImpl);
        $config->setQueryCacheImpl($cache);
        $config->setResultCacheImpl(new ArrayCache);

        // Proxy configuration
        $proxyDir = APPLICATION_PATH . '/var/doctrine/Proxies';

        if (!is_dir($proxyDir)) {
            mkdir($proxyDir);
        }

        Loader::addLibrary('Proxies', array('path' => $proxyDir));

        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace('Proxies');

        // Database connection information
        // $connectionOptions = require __DIR__ . '/config/bootstrap/db-config.php';
        $connectionOptions = [
            'driver'   => 'pdo_sqlite',
            'path'     => APPLICATION_PATH . '/var/db.sq3',
        ];

        // Create EntityManager
        $em = EntityManager::create($connectionOptions, $config);
        Registry::getInstance()->doctrineEm = $em;
        $conn = $em->getConnection();
        
        /** @var $em \Doctrine\ORM\EntityManager */
        $platform = $conn->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('bit', 'string');
        
        $jsonType = Type::getType('json');
        $conn->getDatabasePlatform()->markDoctrineTypeCommented($jsonType);

        if ($opts['queryLogger'] && $opts['queryLogger']['enable']) {
            $logFile = ($opts['queryLogger']['filename'] ?: false);
            $queryLogger = new QueryLogger($logFile);
            Registry::getInstance()->queryLogger = $queryLogger;
            $conn->getEventManager()->addEventSubscriber($queryLogger);
            $conn->getConfiguration()->setSQLLogger($queryLogger);
        }
    }

    protected function _initSession() {
        if (Environment::isCli()) {
            return;
        }

        try {
            Zend_Session::setOptions(array(
                'use_only_cookies' => true,
            ));
            
            if (Zend_Session::sessionExists()) {
                Zend_Session::start();
            }
            // Zend_Session::rememberMe(3600*24*14);
        } catch (Exception $e) {
            if (session_id()) {
                @session_destroy();
            }

            if ('localdevelopment' !== APPLICATION_ENV) {
                die('Unable to load page. Please try again.');
            }

            throw $e;
        }
    }


    protected function _initRouter() {
        $this->bootstrap(['frontcontroller']);
        $front = $this->getResource('frontcontroller');

        if (Environment::isCli()) {
            $router = new CliRouter();
            $front->setRequest(new Zend_Controller_Request_Simple());
            $front->setResponse(new Zend_Controller_Response_Cli());
            $front->setParam('disableOutputBuffering', true);
        } else {
            $router = new Zend_Controller_Router_Rewrite();
        }

        $front->setRouter($router);
        return $router;
    }

    protected function _initRoutes() {
        $this->bootstrap('router');
        $router = $this->getResource('router');

        if (!($router instanceof Zend_Controller_Router_Rewrite)) {
            return;
        }
        
        $homeRoute = array(
            'module'     => 'default',
            'controller' => 'index',
            'action'     => 'index',
        );
        
        if (!Environment::isCli() && Zend_Session::isStarted()) {
            $auth = Auth::getInstance();

            if ($auth->hasIdentity()) {
                $homeRoute['action'] = 'home';
            }
        }

        $router->addRoutes(array(
            'home' => new StaticRoute('/', $homeRoute),
            'about' => new StaticRoute('about', array(
                'module'     => 'default',
                'controller' => 'index',
                'action'     => 'about',
            )),
            'auth.login' => new StaticRoute('login', array(
                'module'     => 'default',
                'controller' => 'auth',
                'action'     => 'login',
            )),
            'auth.logout' => new StaticRoute('logout', array(
                'module'     => 'default',
                'controller' => 'auth',
                'action'     => 'logout',
            )),
            'settings' => new StaticRoute('settings', array(
                'module'     => 'default',
                'controller' => 'member',
                'action'     => 'settings',
            )),
            'schema' => new Route('object/schema/:schemaname', array(
                'module'       => 'default',
                'controller'   => 'object',
                'action'       => 'schema',
                'schemaname'   => '',
            )),
            'schemaExport' => new Route('object/schema/:schemaname/export', array(
                'module'       => 'default',
                'controller'   => 'object',
                'action'       => 'schema-export',
                'schemaname'   => '',
            )),
            'table' => new Route('object/table/:schema/:table', array(
                'module'       => 'default',
                'controller'   => 'object',
                'action'       => 'table',
                'schema'       => '',
                'table'        => '',
            )),
            'tableExport' => new Route('object/table/:id/export', array(
                'module'       => 'default',
                'controller'   => 'object',
                'action'       => 'table-export',
                'id'           => '',
            )),
            'chapterShingles' => new Route('chapter/:slug/shingles', array(
                'module'       => 'default',
                'controller'   => 'chapter',
                'action'       => 'shingles',
                'slug'         => '',
            )),
            'chapterMembershipCards' => new Route('chapter/:slug/membership-cards', array(
                'module'       => 'default',
                'controller'   => 'chapter',
                'action'       => 'membership-cards',
                'slug'         => '',
            )),
            'chapterById' => new RegexRoute(
                'chapter/(\d+)', array(
                    'module'     => 'default',
                    'controller' => 'chapter',
                    'action'     => 'index',
                    'id'         => 0,
                ),
                array(1 => 'id'),
                'chapter/%d'
            ),
            'chapterActionById' => new RegexRoute(
                'chapter/(\d+)/(.+?)',
                array(
                    'module'     => 'default',
                    'controller' => 'chapter',
                    'id'         => 0,
                    'action'     => 'index',
                ),
                array(
                    1 => 'id',
                    2 => 'action',
                ),
                'chapter/%d/%s'
            ),
            'chapterPdf' => new Route('chapter/pdf', array(
                'module'     => 'default',
                'controller' => 'chapter',
                'action'     => 'pdf',
            )),
            'chapterCreate' => new Route('chapter/create', array(
                'module'     => 'default',
                'controller' => 'chapter',
                'action'     => 'create',
            )),
            'chapterSearch' => new Route('chapter/search', array(
                'module'     => 'default',
                'controller' => 'chapter',
                'action'     => 'search',
            )),
            'memberView' => new RegexRoute(
                'member/(\d+)',
                array(
                    'module'     => 'default',
                    'controller' => 'member',
                    'id'         => 0,
                    'action'     => 'index',
                ),
                array(
                    1 => 'id',
                ),
                'member/%d'
            ),
            'member' => new RegexRoute(
                'member/(\d+)/(.+?)',
                array(
                    'module'     => 'default',
                    'controller' => 'member',
                    'id'         => 0,
                    'action'     => 'index',
                ),
                array(
                    1 => 'id',
                    2 => 'action',
                ),
                'member/%d/%s'
            ),
            'submission' => new RegexRoute(
                'submission/(.+?)/(\d+)/(.+?)',
                array(
                    'module'     => 'default',
                    'controller' => 'submission',
                    'id'         => 0,
                    'type'       => '',
                    'action'     => 'index',
                ),
                array(
                    1 => 'type',
                    2 => 'id',
                    3 => 'action',
                ),
                'submission/%s/%d/%s'
            ),
            'submissionCreate' => new Route(':chapter/submission/:type/create', array(
                'module'     => 'default',
                'controller' => 'submission',
                'type'       => '',
                'chapter'    => '',
                'action'     => 'create',
            )),
            'submissionShingles' => new Route('submission/:submission/shingles', array(
                'module'       => 'default',
                'controller'   => 'submission',
                'action'       => 'shingles',
                'slug'         => '',
            )),
            'submissionStatusEdit' => new Route('submission/edit-status/:type/:submission', array(
                'module'       => 'default',
                'controller'   => 'submission',
                'action'       => 'edit-status',
                'slug'         => '',
            )),
            'submissionMembershipCards' => new Route('submission/:submission/membership-cards', array(
                'module'       => 'default',
                'controller'   => 'submission',
                'action'       => 'membership-cards',
                'slug'         => '',
            )),
            'deleteSubmission' => new Route('submission/delete', array(
                'module'     => 'default',
                'controller' => 'submission',
                'action'     => 'delete',
            )),
            'officerAndChapterUpdate' => new Route('officer-and-chapter-update/:submission', array(
                'module'     => 'default',
                'controller' => 'officer-and-chapter-update',
                'action'     => 'index',
                'submission' => 0,
            )),
        ));
    }
}

