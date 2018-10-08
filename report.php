<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '1024M');


require_once 'vendor/autoload.php';

use DeviceDetector\DeviceDetector;


// file with all top useragents generated with:
//     https://github.com/matomo-org/device-detector#listing-all-user-agents-from-your-logs
$user_agents_file = __DIR__ . '/data/top-user-agents.txt';
// useragent lines contains count how much it UA as often met on the logs
$user_agents_countains_counts = true;
// md5 of useragents file
$user_agents_file_md5 = md5_file($user_agents_file);

// results file
$results_file = __DIR__ . '/results/' . $user_agents_file_md5 . '.html';
// results file location
$results_file_loc = str_replace(__DIR__, '', $results_file);



// if report for current useragents already created - redirect
if (file_exists($results_file)) {
    header('Location: ' . $results_file_loc);
    exit();
}



$tpl_start = file_get_contents(__DIR__ . '/result_tpl/start.html');
$tpl_start = str_replace('[UA_FILE]', $user_agents_file, $tpl_start);

$tpl_row = file_get_contents(__DIR__ . '/result_tpl/row.html');

$tpl_end = file_get_contents(__DIR__ . '/result_tpl/end.html');


$user_agents_data = file($user_agents_file);
$dd = new DeviceDetector();
$results_handler = fopen($results_file, 'w');
fwrite($results_handler, $tpl_start);


$line_pos = 1;
foreach ($user_agents_data as $ua) {
    $ua = trim($ua);
    if ($user_agents_countains_counts) {
        $first_space = mb_strpos($ua, ' ');
        $ua = mb_substr($ua, $first_space + 1);
    }


    $dd->setUserAgent($ua);
    $dd->parse();

    $ua_is_bot = $dd->isBot();
    $clientInfo = $osInfo = $device = $brand = $model = '-';
    $isBotClass = 'alert-danger';
    if (!$ua_is_bot) {
        $isBotClass = '';
        $clientInfo = $dd->getClient();
        $osInfo = $dd->getOs();
        $device = $dd->getDeviceName();
        $brand = $dd->getBrandName();
        $model = $dd->getModel();
    }

    $current_row = str_replace(
        array(
            '[isBotClass]',
            '[N]',
            '[UA]',
            '[isBot]',
            '[getClient]',
            '[getOs]',
            '[getDeviceName]',
            '[getBrandName]',
            '[getModel]'
        ),
        array(
            $isBotClass,
            $line_pos,
            htmlspecialchars($ua),
            (($ua_is_bot == 1) ? "True" : 'False'),
            var_export($clientInfo, true),
            var_export($osInfo, true),
            $device,
            $brand,
            $model
        ),
        $tpl_row
    );

    fwrite($results_handler, $current_row);

    $line_pos++;
}

fwrite($results_handler, $tpl_end);

fclose($results_handler);

echo 'Done! Report generated: <a href="' . $results_file_loc . '">view</a>.';
