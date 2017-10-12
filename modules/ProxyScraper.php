<?php
namespace phpBrute\Module;

use \phpBrute\Helper;
use \phpBrute\Result;

class ProxyScraper extends \phpBrute\Module
{
    public $info = [
        'name' => 'ProxyScraper',
        'author' => 'Yani',
        'version' => 1.0,
        'info' => 'A proxyscraper module for phpBrute. Scrapes proxies of a list of websites. (IPv4 only)',
        'input_format' => "<url>",
        'settings' => [
            'time-out' => [true, 'The connection timeout in seconds']
        ]
    ];

    public function getIPv4(string $string): array
    {
        if (preg_match_all('~(?:(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.){3}(?:25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9]):(?:6553[0-5]|655[0-2][0-9]|65[0-4][0-9]{2}|6[0-4][0-9]{3}|[1-5][0-9]{4}|[1-9][0-9]{1,3}|[0-9])~', $string, $matches)) {
            if (!empty($matches[0]) && is_array($matches[0])) {
                return $matches[0];
            }
        }
        return [];
    }

    public function run($data, $proxy, $useragent, array $settings = [], array $run_once_data = [])
    {
        if (filter_var($data['url'], FILTER_VALIDATE_URL) === false) {
            return $this->return(self::INVALID);
        }

        if ($this->curl = new CurlHandle($proxy, $useragent)) {
            $this->curl->setOpts('CURLOPT_URL', $data['url']);

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

            if (!$output = curl_exec($this->curl)) {
                return $this->return(self::INVALID);
            }

            if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) != 200) {
                return $this->return(self::INVALID);
            }

            $proxies = [];
            $proxies = array_merge($proxies, $this->getIPv4($output));

            if (!empty($proxies)) {
                return $this->return(self::SUCCESS, $proxies);
            }

            return $this->return(self::INVALID);
        }

        return $this->return(self::FAIL);
    }
}
