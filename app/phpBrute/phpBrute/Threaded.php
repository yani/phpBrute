<?php

namespace phpBrute;

class Threaded extends \Threaded
{
    public $module;

    public $input_data;

    public $original_input;

    public function __construct($input_data, $original_input)
    {
        $this->input_data     = $input_data;
        $this->original_input = $original_input;
    }

    public function run()
    {
        try {
            if ($this->module = $this->worker->getModule()) {
                include(PHPBRUTE_APP_DIR . '/Autoload.php');
                $return_arr = $this->module->run($this->input_data, $this->worker->getProxy(), $this->worker->getUseragent(), $this->worker->settings, $this->worker->run_once_data);
                $return = $return_arr[0];
                $return_output = (!empty($return_arr[1]) ? implode($this->worker->data_delimiter, $return_arr[1]) : $this->original_input) . PHP_EOL;
                $return_cli = !empty($return_arr[2]) ? $return_arr[2] : $this->original_input;

                switch ($return) {
                    case 2:
                        CLI::print(CLI::color("SUCCESS", "lime") . " {$return_cli}");
                        $this->worker->writeOutput($return_output);
                        return true;
                    case 1:
                        CLI::print(CLI::color("PARTIAL", "yellow") . " {$return_cli}", false);
                        $this->worker->writeOutputPartial($return_output);
                        return true;
                    case 0:
                        CLI::print(CLI::color("INVALID", "red") . " {$return_cli}", false);
                        return true;
                    case -1:
                    default:
                        CLI::debug(CLI::color("FAIL    {$return_cli}", "dark-gray", false));
                        return $this->run();
                }
            } else {
                throw new \Exception("failed to grab module...");
            }
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            if (defined('PHPBRUTE_DEBUG')) {
                CLI::print(CLI::color("EXCEPTION", "black", "red") . " {$return_cli}\n          {$msg}", false);
            } else {
                CLI::print(CLI::color("EXCEPTION", "black", "red") . " {$return_cli}", false);
            }
        }

        if ($this->module) {
            unset($this->module);
        }

        return $this->run();
    }
}
