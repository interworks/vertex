<?php

use vox\core\Loader;
use vox\core\Environment;
use vox\storage\Cache;
use lithium\storage\cache\adapter\Apc;

define('APP_CACHE_KEY_PREFIX', APPLICATION_NAME . '_' . Environment::is() . '_');

/** @ignore */ 
call_user_func(function () {
    if (!Environment::isCli() && Apc::enabled()) { // && Environment::is(array('staging', 'production', 'development'))) {
        $adapter = 'Apc';
    } else {
        $adapter = 'Memory';
    }

    Cache::config(array(
        'default' => array(
            'adapter' => Loader::find('adapter.storage.cache', $adapter),
            'filters' => array(function ($self, $params, $chain) {
                $params['key'] = APP_CACHE_KEY_PREFIX . $params['key'];
                return $chain->next($self, $params, $chain);
            }),
        )
    ));

    if ($cache = Cache::read('default', 'loader_libraries')) {
        Loader::cache($cache);
    }

    register_shutdown_function(function () use ($cache) {
        if ($cache != Loader::cache()) {
            Cache::write('default', 'loader_libraries', Loader::cache(), '+1 day');
        }
    });
});
