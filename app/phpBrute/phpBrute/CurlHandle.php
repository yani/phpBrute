<?php

namespace phpBrute;

class CurlHandle
{
    public $handle;

    public $cookies;

    public $opts;

    public $output;

    public $return_code;

    private $default_opts = [
        'CURLOPT_HTTPHEADER' => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Charset: utf-8',
            'Accept-Language: en-US,en;q=0.8'
        ],
        'CURLOPT_ENCODING' => '',
        'CURLOPT_COOKIEJAR' => false,
        'CURLOPT_RETURNTRANSFER' => 1,
        'CURLOPT_FOLLOWLOCATION' => true,
        'CURLOPT_SSL_VERIFYHOST' => false,
        'CURLOPT_SSL_VERIFYPEER' => false
    ];

    private function _initHandle()
    {
        if (!$this->handle = curl_init()) {
            throw new \Exception('Failed to initialize cURL handle');
        }
    }

    public function __construct($proxy = false, $useragent = null)
    {
        // Setup handle
        $this->_initHandle();

        // Set default opts
        $this->setOpts($this->default_opts);

        // Add useragent
        $this->setOpt('CURLOPT_USERAGENT', $useragent ?? 'phpBrute (https://github.com/yanikore/phpBrute)');

        // Add proxy
        $this->setOpt('CURLOPT_PROXY', $proxy);

        return $this;
    }

    private function _setHeaderFunction()
    {
        // Reference
        $handle = &$this->handle;
        $cookies = &$this->cookies;

        // Callback on the header to grab cookies
        curl_setopt($handle, CURLOPT_HEADERFUNCTION, function (&$handle, $header_line) use (&$cookies) {
            // https://stackoverflow.com/a/25098798/5865844
            if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $header_line, $cookie) === 1) {
                if (strpos($cookie[0], '=') !== false) {
                    $cookie_exp = explode("=", $cookie[0], 2);
                    $cookie_name = trim(substr($cookie_exp[0], 11));
                    $cookies[$cookie_name] = $cookie_exp[1];
                }
            }
            return strlen($header_line); // Needed by curl
        });
    }

    private function _handleOptString(string $opt)
    {
        $opt = strtoupper($opt);
        if (substr($opt, 0, 8) !== 'CURLOPT_') {
            $opt = 'CURLOPT_' . $opt;
        }
        return $opt;
    }

    public function getOpt(string $opt)
    {
        return $this->opts[$this->_handleOptString($opt)] ?? null;
    }

    public function reset(bool $keep_opts = false, bool $keep_cookies = false)
    {
        if (!$keep_opts) {
            $this->opts = [];
        }
        if (!$keep_cookies) {
            $this->cookies = [];
        }
    }

    public function setOpts(array $opts)
    {
        foreach ($opts as $opt => $value) {
            $this->setOpt($opt, $value);
        }
    }

    public function setOpt(string $opt, $value)
    {
        $opt = $this->_handleOptString($opt);
        $this->opts[$opt] = $value;
        return curl_setopt($this->handle, constant($opt), $value);
    }

    private function _configureCurl()
    {
        $this->_initHandle();
        $this->setOpts($this->opts);
        if (!empty($this->cookies)) {
            curl_setopt($this->handle, CURLOPT_COOKIE, $this->_getCookieString());
        }
        // Add callback on the header to grab cookies
        $this->_setHeaderFunction();
    }

    private function _cloudFlareBypass()
    {
        try {
            $var_str = 's,t,o,p,b,r,e,a,k,i,n,g,f, ';
            if ($this->output) {
                if ((int)curl_getinfo($this->handle, CURLINFO_HTTP_CODE) === 503 && Helper::find($this->output, $var_str)) {
                    $effective_url = curl_getinfo($this->handle, CURLINFO_EFFECTIVE_URL);
                    $parse_url = parse_url($effective_url);
                    $domain = $parse_url['host'];
                    $domain_length = @strlen($domain);
                    if ($domain_length > 0) {
                        $line1 = Helper::getBetween($this->output, $var_str, ';') . ';';
                        $line2 = strstr(
                                Helper::getBetween($this->output, "document.getElementById('challenge-form');", 't.length;') . $domain_length . ';',
                                explode('=', $line1)[0]
                            );
                        $ret = (new \V8Js())->executeString('var ' . $line1 . str_replace('a.value', '$', $line2));
                        if (is_numeric($ret)) {
                            $url = $parse_url['scheme'] . '://' . $domain . '/cdn-cgi/l/chk_jschl?' . http_build_query([
                                    'jschl_vc' => Helper::getBetween($this->output, 'jschl_vc" value="', '"'),
                                    'pass' => htmlentities(Helper::getBetween($this->output, 'pass" value="', '"')),
                                    'jschl_answer' => $ret
                                ]);
                            $this->_configureCurl(); // Configuration is required to load cookies
                            curl_setopt_array($this->handle, [
                                    CURLOPT_URL => $url,
                                    CURLOPT_REFERER => $effective_url
                                ]);
                            sleep(4);
                            $this->output = curl_exec($this->handle);
                            $this->return_code = (int)curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
                            return true;
                        }
                    }
                    $this->output = false;
                }
            }
        } catch (\Exception $ex) {
            $this->output = false;
        }
    }

    private function _blazingFastBypass()
    {
        try {
            $var_str = 'xhr.open("GET","/___S___/?rid=';
            if ($this->output) {
                if ($this->return_code === 200 && Helper::find($this->output, $var_str)) {
                    $effective_url = curl_getinfo($this->handle, CURLINFO_EFFECTIVE_URL);
                    $parse_url = parse_url($effective_url);
                    $domain = $parse_url['host'];
                    if (strlen($rid = Helper::getBetween($this->output, $var_str, '&')) == 64) {
                        $sizes = ['1080', '768', '800', '1024'];
                        $sid = $sizes[random_int(0, count($sizes) - 1)];
                        $tz = time() . '.' . random_int(10, 99);
                        $this->_configureCurl();
                        curl_setopt_array($this->handle, [
                            CURLOPT_URL => $parse_url['scheme'] . '://' . $domain . '/___' . 'S' . '___/?rid=' . $rid . '&sid=' . $sid . '&d=' . $domain . '&tz=' . $tz,
                            CURLOPT_REFERER => $effective_url
                        ]);
                        if ($this->output = curl_exec($this->handle)) {
                            $this->return_code = (int)curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
                            if ($this->return_code === 200 && Helper::find($this->output, 'var BFCrypt=') && Helper::find($this->output, 'if($(window).width()>0)')) {
                                $ret = (new \V8Js())->executeString('var BFCrypt=' . Helper::getBetween($this->output, 'var BFCrypt=', 'if($(window).width()>0)') . '$ = toHex(BFCrypt.decrypt(c, 2, a, b));');
                                if (strlen($ret) == 32) {
                                    $this->setCookie('BLAZINGFAST-WEB-PROTECT', $ret);
                                    $this->_configureCurl(); // Configuration is required to load cookies
                                    curl_setopt_array($this->handle, [
                                        CURLOPT_URL => $effective_url,
                                        CURLOPT_REFERER => $effective_url
                                    ]);
                                    $this->output = curl_exec($this->handle);
                                    $this->return_code = (int)curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
                                    return true;
                                }
                            }
                        }
                    }

                    $this->output = false;
                }
            }
        } catch (\Exception $ex) {
            $this->output = false;
        }
    }

    private function _getCookieString()
    {
        $cookie_strings = [];
        foreach ($this->cookies as $cookie_name => $cookie_value) {
            $cookie_strings[] = $cookie_name . '=' . $cookie_value;
        }
        return implode('; ', $cookie_strings);
    }

    public function removeCookie(string $cookie_name)
    {
        if (isset($this->cookies[$cookie_name])) {
            unset($this->cookies[$cookie_name]);
        }
    }

    public function setCookie(string $cookie_name, string $cookie_value)
    {
        if ($cookie_value == null) {
            $this->removeCookie($cookie_name);
        } else {
            $this->cookies[$cookie_name] = $cookie_value;
        }
    }

    public function setCookies(array $cookie_array)
    {
        foreach ($cookie_array as $cookie_name => $cookie_value) {
            $this->setCookie($cookie_name, $cookie_value);
        }
    }

    private function _exec()
    {
        $this->_configureCurl();
        $return = curl_exec($this->handle);
        $this->return_code = (int)curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
        return $return;
    }

    public function exec()
    {
        $this->output = $this->_exec();
        if (class_exists('\\V8Js')) {
            $this->_cloudFlareBypass();
            $this->_blazingFastBypass();
        }
        return $this->output;
    }

    public function close()
    {
        if ($this->curl) {
            @curl_close($this->curl);
        }
    }
}
