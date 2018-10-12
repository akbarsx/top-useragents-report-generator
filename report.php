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

//
$rg = new ReportGenerator($user_agents_file, $user_agents_countains_counts);
$rg->process();



class ReportGenerator {

    private $ua_path;
    private $contains_counts;

    private $results_file;
    private $results_file_loc;

    private $tpl_start;
    private $tpl_row;
    private $tpl_end;

    public function __construct($ua_path, $contains_counts) {
        $this->ua_path = $ua_path;
        $this->contains_counts = $contains_counts;

        // md5 of useragents file
        $user_agents_file_md5 = md5_file($this->ua_path);

        // results file
        $this->results_file = __DIR__ . '/results/' . $user_agents_file_md5 . '.html';
        // results file location
        $this->results_file_loc = str_replace(__DIR__, '', $this->results_file);

        $this->init_templates();
    }

    // if report for current useragents already created - redirect
    private function check_report() {
        if (file_exists($this->results_file)) {
            header('Location: ' . $this->results_file_loc);
            echo 'Report already generated: <a href="' . $this->results_file_loc . '">view</a>.';
            exit();
        }
    }

    private function init_templates() {
        $this->tpl_start = file_get_contents(__DIR__ . '/result_tpl/start.html');
        $this->tpl_start = str_replace('[UA_FILE]', $this->ua_path, $this->tpl_start);

        $this->tpl_row = file_get_contents(__DIR__ . '/result_tpl/row.html');

        $this->tpl_end = file_get_contents(__DIR__ . '/result_tpl/end.html');
    }

    public function process() {
        $this->check_report();

        $user_agents_data = file($this->ua_path);
        $dd = new DeviceDetector();
        $results_handler = fopen($this->results_file, 'w');
        fwrite($results_handler, $this->tpl_start);


        $line_pos = 1;
        foreach ($user_agents_data as $ua) {
            $ua = trim($ua);
            if ($this->contains_counts) {
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
                    $this->escape_row_data(htmlspecialchars($ua)),
                    (($ua_is_bot == 1) ? "True" : 'False'),
                    $this->escape_row_data(var_export($clientInfo, true)),
                    $this->escape_row_data(var_export($osInfo, true)),
                    $this->escape_row_data($device),
                    $this->escape_row_data($brand),
                    $this->escape_row_data($model)
                ),
                $this->tpl_row
            );

            fwrite($results_handler, $current_row);

            $line_pos++;
        }

        fwrite($results_handler, $this->tpl_end);

        fclose($results_handler);

        echo 'Done! Report generated: <a href="' . $this->results_file_loc . '">view</a>.';
    }

    private function escape_row_data($data) {
        return str_replace(
            array(
                "\r\n",
                "\r",
                "\n",
                '   ',
                '  ',
                "'"
            ),
            array(
                ' ',
                ' ',
                ' ',
                ' ',
                ' ',
                "\'"
            ),
            $data
        );
    }
}