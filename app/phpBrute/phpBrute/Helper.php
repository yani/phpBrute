<?php
namespace phpBrute;

class Helper
{
    public static function find(string $input, string $find_string)
    {
        return (strpos($input, $find_string) !== false);
    }

    public static function getBetween($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    public static function loadcURL($proxy = false, $useragent = false)
    {
        try {
            if ($curl_handle = curl_init()) {
                curl_setopt_array($curl_handle, [
                    CURLOPT_HTTPHEADER => [
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                        'Accept-Charset' => 'utf-8',
                        'Accept-Language' => 'en-US,en;q=0.8'
                    ],
                    CURLOPT_ENCODING => '',
                    CURLOPT_COOKIEJAR => false,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_FOLLOWLOCATION => 1,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_USERAGENT => ($useragent) ? $useragent : //'phpBrute (https://github.com/yanikore/phpBrute)'
                        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36'
                ]);

                if ($proxy) {
                    curl_setopt($curl_handle, CURLOPT_PROXY, $proxy);
                }

                return $curl_handle;
            }
        } catch (\Exception $ex) {
        }
        return false;
    }

    public static function cloudflareGet(&$curl_handle) // curl->cookiejar must be defined
    {
        $var_str = 's,t,o,p,b,r,e,a,k,i,n,g,f, ';
        if ($output = curl_exec($curl_handle)) {
            if ((int)curl_getinfo($curl_handle, CURLINFO_HTTP_CODE) === 503 && self::find($output, $var_str)) {
                $effective_url = curl_getinfo($curl_handle, CURLINFO_EFFECTIVE_URL);
                $url_data = @parse_url($effective_url);
                $domain = $url_data['host'];
                $domain_length = @strlen($domain);
                if ($domain_length > 1) {
                    try {
                        $v8 = new \V8Js();
                        $line1 = self::getBetween($output, $var_str, ';') . ';';
                        $line2 = strstr(
                            self::getBetween($output, "document.getElementById('challenge-form');", 't.length;') . $domain_length . ';',
                            explode('=', $line1)[0]
                        );
                        $ret = $v8->executeString('var ' . $line1 . str_replace('a.value', '$', $line2));
                        if (is_numeric($ret)) {
                            $url = $url_data['scheme'] . '://' . $domain . '/cdn-cgi/l/chk_jschl?' . http_build_query([
                                'jschl_vc' => self::getBetween($output, 'jschl_vc" value="', '"'),
                                'pass' => htmlentities(self::getBetween($output, 'pass" value="', '"')),
                                'jschl_answer' => $ret
                            ]);

                            curl_setopt_array($curl_handle, [
                                CURLOPT_URL => $url,
                                CURLOPT_REFERER => $effective_url
                            ]);

                            sleep(4);

                            return curl_exec($curl_handle);
                        }
                    } catch (\Exception $ex) {
                        throw new \Exception($ex->getMessage());
                    }
                }
            } else {
                return $output;
            }
        }
        return false;
    }

    public static function deathByCaptchaBalance(string $user, string $pass)
    {
        try {
            $client = new \hmphu\deathbycaptcha\DeathByCaptchaSocketClient($user, $pass);
            return round($client->get_balance() / 100, 2);
        } catch (\hmphu\deathbycaptcha\DeathByCaptchaException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function deathByCaptcha($image, string $user, string $pass, int $timeout = 45)
    {
        try {
            $client = new \hmphu\deathbycaptcha\DeathByCaptchaSocketClient($user, $pass);
            $balance = $client->get_balance();

            if ($balance <= 0) {
                throw new \Exception("DeathByCaptcha: not enough balance");
            }

            $captcha = $client->decode($image, $timeout);

            if ($captcha && !empty($captcha['text'])) {
                return $captcha['text'];
            }
        } catch (\hmphu\deathbycaptcha\DeathByCaptchaException $e) {
            throw new \Exception('DeathByCaptcha: ' . $e->getMessage());
        }

        return false;
    }
}
