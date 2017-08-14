<?php
namespace phpBrute\Module;

use \phpBrute\Helper;

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
        $urls = [
            'http://v4.ipv6-test.com/api/myip.php',
            'http://v6.ipv6-test.com/api/myip.php',
            'http://v4v6.ipv6-test.com/api/myip.php',
            'https://api.ipify.org/?format=text',
            /*'http://bot.whatismyipaddress.com/',
            'http://ipv4bot.whatismyipaddress.com',
            'http://ipv6bot.whatismyipaddress.com'*/
        ];

        if (!$this->curl = Helper::loadcURL()) {
            throw new \Exception('failed to load cURL');
        }

        foreach ($urls as $url) {
            try {
                curl_setopt($this->curl, CURLOPT_URL, $url);

                if ($output = curl_exec($this->curl)) {
                    $ip = trim($output);
                    if (filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, $run_once_data)) {
                        $run_once_data[] = $ip;
                    }
                }
            } catch (\Exception $ex) {
                // nothing
            }
        }

        @curl_close($this->curl);

        if (empty($run_once_data)) {
            throw new \Exception('failed to grab local IP');
        }

        return $run_once_data;
    }

    public function run($data, $proxy, $useragent, array $settings = [], array $run_once_data = [])
    {
        if ($this->curl = Helper::loadcURL($data['proxy'], $useragent)) {
            if (!empty($settings['judge-url']) && filter_var($data['judge-url'], FILTER_VALIDATE_URL) !== false) {
                curl_setopt($this->curl, CURLOPT_URL, $settings['judge-url']);
            } else {
                curl_setopt($this->curl, CURLOPT_URL, 'http://proxyjudge.us/azenv.php');
            }

            if (!empty($settings['time-out'])) {
                curl_setopt_array($this->curl, [
                    CURLOPT_CONNECTTIMEOUT  => (int)$settings['time-out'],
                    CURLOPT_TIMEOUT => (int)$settings['time-out']
                ]);
            } else {
                curl_setopt_array($this->curl, [
                    CURLOPT_CONNECTTIMEOUT  => 20,
                    CURLOPT_TIMEOUT => 20
                ]);
            }

            if (!$output = curl_exec($this->curl)) {
                return $this->return(self::INVALID);
            }

            if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) == 200) {
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
