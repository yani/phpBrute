<?php

namespace phpBrute;

class Module
{
    const SUCCESS  =  2;
    const PARTIAL  =  1;
    const INVALID  =  0;
    const FAIL     = -1;

    public $curl;

    public $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function print(string $string)
    {
        if (php_sapi_name() === 'cli') {
            \phpBrute\CLI::print($string);
        }
    }

    public function return(int $return, array $return_data = [], string $cli_line = '')
    {
        if ($this->curl) {
            @curl_close($this->curl);
        }
        return [$return, $return_data, $cli_line];
    }
}
