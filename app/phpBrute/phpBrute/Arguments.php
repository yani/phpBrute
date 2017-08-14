<?php

namespace phpBrute;

class Arguments
{
    public $count = 0;

    public $script_name;

    public $arguments = [];

    public $argument_list = [];

    public $settings = [];

    public function __construct(array $argument_list)
    {
        $this->argument_list = $argument_list;
    }

    public function load(array $argv)
    {
        $this->script_name = array_shift($argv);
        $this->_getArguments($argv);
        $this->count = count($this->arguments);
    }

    public function get(string $argument)
    {
        return isset($this->arguments[$argument]) ? $this->arguments[$argument] : null;
    }

    public function getSetting(string $setting)
    {
        return !empty($this->settings[$setting]) ? $this->settings[$setting] : false;
    }

    private function _getArguments(array $argv)
    {
        foreach ($argv as $index => $arg) {
            if (substr($arg, 0, 2) === '--') {
                $setting_text = substr($arg, 2, strlen($arg));
                if (strpos($setting_text, '=') !== false) {
                    $exp = explode('=', $setting_text);
                    $var = array_shift($exp);
                    $val = implode('=', $exp);
                    $this->settings[$var] = $val;
                } else {
                    $this->settings[$setting_text] = true;
                }
            } elseif (substr($arg, 0, 1) === '-') {
                $arg_text = substr($arg, 1, strlen($arg));
                foreach ($this->argument_list as $arg_check => $arg_name) {
                    $arg_check_text = str_replace('+', '', $arg_check);
                    if ($arg_text == $arg_check_text) {
                        $this->arguments[$arg_name] = true;
                        if (strpos($arg_check, '+') !== false) {
                            $this->arguments[$arg_name] = [];
                            $extra_count = substr_count($arg_check, '+');
                            for ($i = 1; $i <= $extra_count; $i++) {
                                if (isset($argv[$index + $i]) && substr($argv[$index + $i], 0, 1) !== '-') {
                                    $this->arguments[$arg_name][$i - 1] = $argv[$index + $i];
                                } else {
                                    throw new \Exception("Invalid parameters for {$arg}");
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
