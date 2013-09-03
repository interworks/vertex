#!/usr/bin/env php
<?php
/**
 * Project deployment script
 *
 * Perform a "dry-run" deployment to staging:
 *
 *     ./app/bin/deploy.php staging
 *
 * Deploy to staging:
 *
 *     ./app/bin/deploy.php staging live
 *
 * @package app
 */

define('APP_BASE_PATH', dirname(dirname(__DIR__)));

$rsyncArgs = '--delete --progress --exclude-from=.rsync-exclude --omit-dir-times -Lhvptzcr';

$destinations = array(
    'preview' => array(
        'server' => 'stw-dev-03',
        'user'   => 'kkytbs',
        'host'   => 'kkytbs-oms.preview.interworks.com',
        'path'   => '/home/kkytbs/kkytbs-oms.preview.interworks.com/',
    ),
    'production' => array(
        'server' => 'rs-ln-02',
        'user'   => 'kkytbs',
        'host'   => 'online.kkytbs.org',
        'path'   => '/home/kkytbs/online.kkytbs.org/active/',
    ),
);

$deployTimeFile = APP_BASE_PATH . '/.last-deploy-time';
$prevDeployTime = 0;

chdir(APP_BASE_PATH);

if (file_exists($deployTimeFile)) {
    $prevDeployTime = new DateTime('@' . file_get_contents($deployTimeFile));
}

if (!$argc || $argc <= 2) {
    if ($argc && 2 === $argc) {
        $destKey = $argv[1];
    } else {
        echo "Available destinations: " . implode(', ', array_keys($destinations)) . PHP_EOL;
        exit(1);
    }

    $dryRun = true;
} else {
    $destKey = $argv[1];
    
    if (3 !== $argc || 'live' !== $argv[2]) {
        $dryRun = true;
    } else {
        $dryRun = false;
    }
}

if (!isset($destinations[$destKey])) {
    die("Invalid destination" . PHP_EOL);
}

extract($destinations[$destKey]);

$dest = "{$user}@{$server}:{$path}";

if ($dryRun) {
    echo "DRY RUN!\n";
    $rsyncArgs = "--dry-run {$rsyncArgs}";
} else {
    file_put_contents($deployTimeFile, time());
}

if ($prevDeployTime) {
    $prevDeployTime->setTimeZone(new DateTimeZone(date_default_timezone_get()));
    echo "Last deployment: ". $prevDeployTime->format('F j, Y g:i:sa');
    echo " (" . timeDiff($prevDeployTime) . ")" . PHP_EOL . PHP_EOL;
}

$cmd = "rsync {$rsyncArgs} . {$dest}";
echo $cmd . PHP_EOL . PHP_EOL;
passthru($cmd, $retVal);

if (0 !== $retVal) {
    echo "Non-zero exit: {$retVal}" . PHP_EOL;

    if ($prevDeployTime) {
        file_put_contents($deployTimeFile, $prevDeployTime);
    }
}

$schema = ('production' === $destKey ? 'https' : 'http');

$cmd = "ssh {$user}@{$server} curl -s {$schema}://{$host}/admin/clear-cache";
echo PHP_EOL . $cmd . PHP_EOL . PHP_EOL;

if ($dryRun) {
    echo "(Skipping, dry run)\n";
} else {
    passthru($cmd, $retVal);

    if (0 !== $retVal) {
        echo "Non-zero exit: {$retVal}" . PHP_EOL;
    }
}

$cmd = "ssh {$user}@{$server} {$path}app/bin/doctrine orm:generate-proxies";
echo PHP_EOL . $cmd . PHP_EOL . PHP_EOL;

if ($dryRun) {
    echo "(Skipping, dry run)\n";
} else {
    passthru($cmd, $retVal);

    if (0 !== $retVal) {
        echo "Non-zero exit: {$retVal}" . PHP_EOL;
    }
}

function timeDiff($startTime, $endTime = null) {
    $unitNames = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    $defaults = array(
        'oneUnit'      => true,
        'units'        => array('y','m','w','d','h','i','s'),
        'formatFuture' => 'In %s',
        'formatEqual'  => 'Now',
        'formatPast'   => '%s ago',
        'maxDiff'      => false,
        'dateOptions'  => array(),
    );
    extract($defaults);

    if (!($startTime instanceof DateTime)) {
        $startTime = new DateTime($startTime);
    }

    if (!($endTime instanceof DateTime)) {
        $endTime = new DateTime($endTime);
    }

    if ($startTime == $endTime) {
        return $formatEqual;
    }

    $diff = $startTime->diff($endTime);
    $units = array_intersect_key($unitNames, array_flip($units));

    $ret = array();

    foreach ($units as $unit => $label) {
        if ($unit == 'w') {
            $amt = floor($diff->d / 7);
        } else {
            $amt = $diff->$unit;
        }

        if ($amt) {
            $ret[] = $amt . ' ' . $label . ($amt == 1 ? '' : 's');

            if ($oneUnit) {
                break;
            }
        }
    }

    if (empty($ret)) {
        $last = end($units);
        $ret = 'less than 1 ' . $last;
    } else {
        $ret = implode(' ', $ret);
    }

    if ($startTime > $endTime) {
        return sprintf($formatFuture, $ret);
    }

    return sprintf($formatPast, $ret);
}