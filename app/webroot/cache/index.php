<?php

define('CACHE_DIR', realpath(__DIR__));

$cacheFilename = basename($_SERVER['QUERY_STRING']);
$cacheFile = new SplFileInfo(CACHE_DIR . DIRECTORY_SEPARATOR . $cacheFilename);

if (!$cacheFile->isFile() || !$cacheFile->isReadable()) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

if ('.js' === substr($cacheFilename, -3)) {
    header('Content-Type: text/javascript');
} else if ('.css' === substr($cacheFilename, -4)) {
    header('Content-Type: text/css');
} else {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$mtime = new DateTime($cacheFile->getMTime());

header('Cache-Control: public, must-revalidate');
header('Last-Modified: ' . $mtime->format('r'));

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $clientTime = new DateTime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    
    if ($clientTime === $mtime) {
        header('HTTP/1.0 304 Not Modified');
        exit;
    }
}

ob_clean();
flush();
readfile($cacheFile->getRealPath());
exit;
