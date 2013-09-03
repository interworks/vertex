<?php

use vox\core\Loader;
use vox\core\Environment;

define('VOX_FRAMEWORK_PATH', realpath(VOX_LIBRARY_PATH . '/vox/libraries/vox'));
define('LITHIUM_LIBRARY_PATH', VOX_LIBRARY_PATH);
define('LITHIUM_APP_PATH', APPLICATION_PATH);

require VOX_FRAMEWORK_PATH . '/core/Environment.php';
require VOX_FRAMEWORK_PATH . '/core/Loader.php';

Environment::set($_SERVER);

Loader::template('bootstrap', array(
    '{:library}\{:name}\Bootstrap' => array('app'),
    '{:library}\Bootstrap'         => array('app'),
));
Loader::template('controller', array(
    '{:library}\{:namespace}\controllers\{:name}Controller' => array('app'),
), true);

Loader::addLibraries(array(
    'Zend'    => array('path' => VOX_LIBRARY_PATH . '/Zend/library/Zend'),
    'vox'     => array('path' => VOX_FRAMEWORK_PATH),
    'lithium' => array('path' => LITHIUM_LIBRARY_PATH . '/lithium'),
    'app'     => array(
        'path'      => APPLICATION_PATH,
        'order'     => 10,
        'bootstrap' => false,
    ),
));

Loader::template('extensions', array(
    '{:library}\extensions\{:name}',
));