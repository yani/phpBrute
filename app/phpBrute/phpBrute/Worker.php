<?php

namespace phpBrute;

class Worker extends \Worker
{
    public $module_factory;

    public $proxies;
    public $proxy_count;

    public $useragents;
    public $useragent_count;

    public $output_handle;
    public $output_partial_handle;

    public $settings;
    public $run_once_data;
    public $data_delimiter;

    public function __construct(ModuleFactory &$module_factory, array $proxies, array $useragents, $output_handle, $output_partial_handle, array $settings = [], array $run_once_data = [], $data_delimiter = ',')
    {
        $this->module_factory         =  $module_factory;
        $this->proxies                =  (array)$proxies;
        $this->useragents             =  (array)$useragents;
        $this->proxy_count            =  count($proxies);
        $this->useragent_count        =  count($useragents);
        $this->output_handle          =  $output_handle;
        $this->output_partial_handle  =  $output_partial_handle;
        $this->settings               =  (array)$settings;
        $this->run_once_data          =  (array)$run_once_data;
        $this->data_delimiter         =  $data_delimiter;
    }

    public function getModule()
    {
        return $this->module_factory->produce();
    }

    public function getProxy()
    {
        return $this->proxies[random_int(0, $this->proxy_count - 1)];
    }

    public function getUseragent()
    {
        return $this->useragents[random_int(0, $this->useragent_count - 1)];
    }

    public function writeOutput(string $string)
    {
        if ($this->output_handle) {
            fwrite($this->output_handle, $string);
        }
    }

    public function writeOutputPartial(string $string)
    {
        if ($this->output_partial_handle) {
            fwrite($this->output_partial_handle, $string);
        }
    }
}
