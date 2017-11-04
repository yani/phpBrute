<?php
namespace phpBrute\Module;

use \phpBrute\Helper;
use \phpBrute\CurlHandle;

class ProxyChecker extends \phpBrute\Module
{
    public $info = [
        'name' => 'ProxyChecker',
        'author' => 'Yani',
        'version' => 1.0,
        'info' => "A HTTP proxy checker module for phpBrute.\nSpecify proxies as inputs (-i)! (NOT as proxies: -p)",
        'input_format' => "<proxy>",
        'settings' => [
            'time-out' => [true, 'The connection timeout in seconds'],
            'judge-url' => [true, 'Use a custom azenv judge URL'],
            'proxy-type' => [true, 'Allowed proxy types, comma seperated: l1,l2,l3']
        ]
    ];

    public function runOnce(array $settings = [])
    {
        $run_once_data = [];

        if (!$this->curl = new CurlHandle()) {
            throw new \Exception('failed to initialize cURL');
        }

        // Grab our own IPs so we can check if proxies leak them
        foreach ([
            'http://v4.ipv6-test.com/api/myip.php',
            'http://v6.ipv6-test.com/api/myip.php',
            'http://v4v6.ipv6-test.com/api/myip.php',
            'https://api.ipify.org/?format=text',
            /*'http://bot.whatismyipaddress.com/',
            'http://ipv4bot.whatismyipaddress.com',
            'http://ipv6bot.whatismyipaddress.com'*/
        ] as $url) {
            try {
                $this->curl->setOpt('CURLOPT_URL', $url);
                if ($output = $this->curl->exec()) {
                    $ip = trim($output);
                    if (filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, $run_once_data)) {
                        $run_once_data[] = $ip;
                    }
                }
            } catch (\Exception $ex) {
            }
        }

        if (empty($run_once_data)) {
            throw new \Exception('failed to grab local IP');
        }

        return $run_once_data;
    }

    public function run($data, $proxy, $useragent, array $settings = [], array $run_once_data = [])
    {
        if ($this->curl = new CurlHandle($data['proxy'], $useragent)) {
            if (!empty($settings['judge-url']) && filter_var($data['judge-url'], FILTER_VALIDATE_URL) !== false) {
                $this->curl->setOpt('CURLOPT_URL', $settings['judge-url']);
            } else {
                $this->curl->setOpt('CURLOPT_URL', 'http://proxyjudge.us/azenv.php');
            }

            if (!empty($settings['time-out'])) {
                $this->curl->setOpts([
                    'CURLOPT_CONNECTTIMEOUT'  => (int)$settings['time-out'],
                    'CURLOPT_TIMEOUT' => (int)$settings['time-out']
                ]);
            } else {
                $this->curl->setOpts([
                    'CURLOPT_CONNECTTIMEOUT'  => 20,
                    'CURLOPT_TIMEOUT' => 20
                ]);
            }

            if (!$output = $this->curl->exec()) {
                return $this->return(self::INVALID);
            }

            if ($this->curl->return_code == 200) {
                if (empty($settings['proxy-type'])) {
                    return $this->return(self::SUCCESS);
                }

                $type = 'l1';

                foreach ($run_once_data as $ip) {
                    if (Helper::find($output, $ip)) {
                        $type = 'l3';
                    }
                }

                if ($type != 'l3') {
                    foreach (['PROXY', 'FORWARDED', 'HTTP_VIA'] as $string) {
                        if (Helper::find($output, $string)) {
                            $type = 'l2';
                        }
                    }
                }

                $types = explode(',', $settings['proxy-type']);
                $types = array_map('strtolower', $types);

                if ($type == 'l1' && (in_array('l1', $types) || in_array('elite', $types))) {
                    return $this->return(self::SUCCESS);
                }

                if ($type == 'l2' && (in_array('l2', $types) || in_array('anon', $types) || in_array('anonymous', $types))) {
                    return $this->return(self::SUCCESS);
                }

                if ($type == 'l3' && (in_array('l3', $types) || in_array('trans', $types) || in_array('transparent', $types))) {
                    return $this->return(self::SUCCESS);
                }
            }
        }

        return $this->return(self::INVALID);
    }
}
