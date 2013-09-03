<?php

namespace app;

use APCIterator;
use Exception;
use vox\core\Loader;
use vox\core\Registry;
use vox\core\Environment;
use Zend_Config;
use Zend_Config_Ini;
use Zend_Config_Writer_Array;

class Application extends \Zend_Application {
    protected static $_instance = null;
    protected $_timeMarks = array(
        'start'   => 0,
        'bootEnd' => 0,
    );

    public function __construct($environment, $options = null) {
        $this->_timeMarks['start'] = microtime(true);
        static::$_instance = &$this;
        $this->_environment = $environment;

        if (null !== $options) {
            throw new Exception('Passing options to this particular Application instance is not supported');
        }

        $options = new Zend_Config_Ini(__DIR__ . '/config/application.ini', $environment, true);
        $this->setOptions($options->toArray());
    }

    public function setBootstrap($path, $class = null) {
        throw new Exception('Bootstrap is not configurable');
    }

    public function getBootstrap() {
        if (null === $this->_bootstrap) {
            $this->_bootstrap = Loader::get('bootstrap', $this);
        }

        return $this->_bootstrap;
    }

    public static function &getInstance() {
        return static::$_instance;
    }
    
    public function bootstrap($resource = null) {
        $ret = parent::bootstrap($resource);
        $this->_timeMarks['bootEnd'] = microtime(true);
        return $ret;
    }
    
    public function getBootTime() {
        return ($this->_timeMarks['bootEnd'] - $this->_timeMarks['start']);
    }
    
    public function getTimeSince($type = 'bootEnd') {
        return (microtime(true) - $this->_timeMarks[$type]);
    }

    public static function getApcUserCache() {
        $cachePrefix = preg_quote(APP_CACHE_KEY_PREFIX, '/');

        return iterator_to_array(new APCIterator('user', "/(d2_)?{$cachePrefix}.*/"), false);
    }

    public static function getApcFileCache() {
        $basePath = dirname(APPLICATION_PATH);
        $cachePrefix = preg_quote($basePath, '/');
        $pattern = "/{$cachePrefix}.*/";

        $info = apc_cache_info(false, false);

        $items = array();

        foreach ($info['cache_list'] as $item) {
            if (!$item['filename']) {
                continue;
            }

            if (preg_match($pattern, $item['filename'])) {
                $items[] = $item;
            }
        }

        return $items;
    }

    public static function clearApcUserCache() {
        $keys = array();

        foreach (static::getApcUserCache() as $item) {
            $keys[] = $item['key'];
        }

        return apc_delete($keys);
    }

    public static function clearApcFileCache() {
        $filenames = array();

        foreach (static::getApcFileCache() as $item) {
            $filenames[] = $item['filename'];
        }

        return apc_delete_file($filenames);
    }
}