<?php
/*
    phpBrute
    https://github.com/yanikore/phpBrute

    TODO:
        - Allow threads to be spawned from modules
 */

namespace phpBrute;

// Constants
define('PHPBRUTE_VERSION', '0.5.2');
define('PHPBRUTE_MODULE_DIR', __DIR__ . '/modules');
define('PHPBRUTE_COLOR', true);

// Error reporting
error_reporting(E_ALL);

// Load the application
require_once('app/phpBrute/Autoload.php');

// Check if we're running from the command line
if (php_sapi_name() !== 'cli') {
    CLI::exit(-1, 'phpBrute must be ran from the command line');
}

// Check if we're running PHP 7 or higher
if ((float)phpversion() < 7) {
    CLI::exit(-1, 'phpBrute requires at least PHP 7.0');
}

// Check for cURL
if (!function_exists('curl_version')) {
    CLI::exit(-1, 'phpBrute requires cURL');
}

// Check if pthreads works
if (!class_exists('\Worker') || !class_exists('\Threaded') || !class_exists('\Pool')) {
    CLI::exit(-1, 'phpBrute requires pthreads 3');
} else {
    try {
        $t = new \Thread();
        $t->start();
        unset($t);
    } catch (\Exception $ex) {
        CLI::exit(-1, 'failed to initiliaze pthreads');
    }
}

// Get arguments
try {
    $arg = new Arguments($argv, [
        'm+'    => 'module',
        't+'    => 'threadcount',
        'i+'    => 'input_file',
        'x+'    => 'input_empty',
        's+'    => 'input_string',
        'f+'    => 'input_format',
        'o+'    => 'output_file',
        'a+'    => 'output_file_partial',
        'u+'    => 'useragent_file',
        'p+'    => 'proxy_file',
        'k+'    => 'socks5_file',
        'r+'    => 'proxy_string',
        'd+'    => 'data_delimiter',
        'z'     => 'check_duplicates',
        'h'     => 'help',
        'help'  => 'help',
        'debug' => 'debug'
    ]);
} catch (\Exception $ex) {
    CLI::exit(-1, $ex->getMessage());
}

// Enable debug mode
if ($arg->get('debug')) {
    define('PHPBRUTE_DEBUG', true);
}

// Show help (no args or only -h or -help)
if ($arg->count === 0 || $arg->get('help')) {
    echo PHP_EOL . "   phpBrute v" . PHPBRUTE_VERSION;
    echo PHP_EOL;
    echo PHP_EOL . "      https://github.com/yanikore/phpBrute";
    echo PHP_EOL;
    echo PHP_EOL . "   Usage:";
    echo PHP_EOL;
    echo PHP_EOL . "     -m <name>             the module to be loaded";
    echo PHP_EOL . "     -i <path>             a list of input entries";
    echo PHP_EOL . "     -x <amount>           the amount of empty input entries";
    echo PHP_EOL . "     -s <string>           a string of input entries, seperated by 3 commas: ,,,";
    echo PHP_EOL . "     -f <string>           a custom input format";
    echo PHP_EOL . "     -o <path>             the output file";
    echo PHP_EOL . "     -a <path>             the output file for partial successes";
    echo PHP_EOL . "     -t <amount>           the amount of threads to use (default: 1)";
    echo PHP_EOL . "     -p <path>             a list of proxies to use";
    echo PHP_EOL . "     -r <string>           a string of proxies to use, seperated by a comma";
    echo PHP_EOL . "     -k <path>             a list of socks5 proxies to use";
    echo PHP_EOL . "     -u <path>             a list of useragents to use";
    echo PHP_EOL;
    echo PHP_EOL . "     -d <string>           a delimiter for outputting module data (default: \\n)";
    echo PHP_EOL . "     -z                    check and remove duplicate entries (slower)";
    echo PHP_EOL;
    echo PHP_EOL . "     -h, -help             show this help dialog";
    echo PHP_EOL . "     -debug                enable debugging of core features and modules";
    echo PHP_EOL;
    echo PHP_EOL . "   Module settings:";
    echo PHP_EOL;
    echo PHP_EOL . "     --<variable>=<value>  module specific variable";
    echo PHP_EOL . "     --<flag>              module specific flag";
    echo PHP_EOL;
    exit(1);
}

// Show startup
CLI::print('phpBrute v' . PHPBRUTE_VERSION);

// Variables
$inputs = [];
$proxies[0] = false;
$useragents[0] = false;
$output_handle = false;
$output_partial_handle = false;
$threadcount = 1;
$data_delimiter = PHP_EOL;
$input_format = '<identifier>';

// Get module info
if (!$module_name = $arg->get('module')) {
    CLI::exit(-1, "no module specified (-m)");
} else {
    $module_path = PHPBRUTE_MODULE_DIR . '/' . $module_name . '.php';
}

// Load module
$run_once_data = [];
CLI::debug("loading module: {$module_name}");
try {
    $module_factory = new ModuleFactory($module_path);
    $module = $module_factory->produce();
    $module_info = $module->info;
    CLI::print("module loaded: {$module_name}");
} catch (\Exception $ex) {
    CLI::exit(-1, "{$module_name}: " . $ex->getMessage());
}

// Show the module info when no other arguments are specified
if ($arg->count == 1) {
    if ($module_info) {
        $info = str_repeat('=', 69) . PHP_EOL;
        if (!empty($module_info['name'])) {
            $info .= 'Name:           ' . $module_info['name'];
        } else {
            $info .= 'Name:           ' . $this->module_name;
        }
        if (isset($module_info['version'])) {
            $info .= PHP_EOL . 'Version:        ' . (string)$module_info['version'];
        }
        if (!empty($module_info['author'])) {
            $info .= PHP_EOL . 'Author:         ' . $module_info['author'];
        }
        if (!empty($module_info['input_format'])) {
            $info .= PHP_EOL . 'Input Format:   ' . CLI::escape($module_info['input_format']);
        }
        if (!empty($module_info['settings']) && is_array($module_info['settings'])) {
            $info .= PHP_EOL . PHP_EOL . 'Settings:';
            foreach ($module_info['settings'] as $setting => $setting_data) {
                $setting_str = '--' . $setting;
                if (!empty($setting_data[0])) {
                    $setting_str .= '=<var>';
                }
                if (!empty($setting_data[1])) {
                    $setting_str .= "\t" . $setting_data[1];
                }
                $info .= PHP_EOL . $setting_str;
            }
        }
        if (!empty($module_info['info'])) {
            $info .= PHP_EOL . PHP_EOL . $module_info['info'];
        }
        CLI::exit(1, $info . PHP_EOL . str_repeat('=', 69));
    } else {
        CLI::exit(0, "no info found for '{$module_name}' module");
    }
}

// Run module runOnce
if (method_exists($module, 'runOnce')) {
    CLI::debug("running module 'run once' function");
    if (!$run_once_data = $module->runOnce($arg->settings)) {
        CLI::exit(-1, "{$module_name}: runOnce failed");
    } else {
        if (!is_array($run_once_data)) {
            $run_once_data = ['success' => true];
        }
    }
} else {
    CLI::debug("module does not have a 'run once' function");
}
unset($module);

// Custom input format
if ($input_format_new = $arg->get('input_format')) {
    $input_format_new_data = Func::formatInput($input_format_new, $input_format_new, false);
    $input_format_data = Func::formatInput($module_info['input_format'], $module_info['input_format'], false);
    $input_format_new_vars = implode(', ', array_keys($input_format_new_data));
    $input_format_vars = implode(', ', array_keys($input_format_data));
    if ($input_format_new_data != $input_format_data) {
        CLI::exit(-1,
        "custom input-format variables need to match\n[{$input_format_new_vars}] != [{$input_format_vars}]");
    }
    $input_format = $input_format_new;
} else {
    if (!empty($module_info['input_format'])) {
        $input_format = $module_info['input_format'];
    }
}

// Load input file
if ($input_file_path = $arg->get('input_file')) {
    CLI::print("loading input file: {$input_file_path}");
    if (!is_readable($input_file_path)) {
        CLI::exit(-1, "failed to read file: {$input_file_path}");
    }
    if (!$input_handle = fopen($input_file_path, 'r')) {
        CLI::exit(-1, "failed to open file handle: {$input_file_path}");
    }
    while (($line = fgets($input_handle)) !== false) {
        $line = trim($line);
        if ($input_format_array = Func::formatInput($line, $input_format)) {
            if (!$arg->get('check_duplicates') || !in_array($input_format_array, $inputs, true)) {
                $inputs[] = $input_format_array;
            } else {
                CLI::debug("duplicate input: {$line}");
            }
        } else {
            CLI::debug("input format mismatch: {$line}");
        }
    }
    @fclose($input_handle);
}

// Load input strings
if ($input_strings = $arg->get('input_string')) {
    foreach (explode("\t", $input_strings) as $input_string) {
        if ($input_format_array = Func::formatInput($input_string, $input_format)) {
            if (!in_array($input_format_array, $inputs, true)) {
                $inputs[] = $input_format_array;
            } else {
                CLI::debug("duplicate input: {$input_string}");
            }
        } else {
            CLI::debug("input format mismatch: {$input_string}");
        }
    }
}

// Load empty inputs
if ($input_empty_amount = $arg->get('input_empty')) {
    if (is_numeric($input_empty_amount)) {
        CLI::debug("extra empty inputs: {$input_empty_amount}");
        for ($i = 0; $i < intval($input_empty_amount); $i++) {
            do {
                $id = substr(sha1($i . microtime() . random_int(0, PHP_INT_MAX)), 0, 16);
                if (!array_key_exists($id, $inputs)) {
                    $inputs[$id] = [
                        'id' => $id,
                        '_original_input' => $id
                    ];
                    break;
                }
            } while (true);
        }
    } else {
        CLI::exit(-1, "invalid amount of empty inputs");
    }
}

// Count inputs
$input_count = count($inputs);
if ($input_count < 1) {
    CLI::exit(-1, "no inputs were loaded");
} else {
    CLI::print("inputs loaded: {$input_count}");
}

// Load proxy file
if ($proxy_file_path = $arg->get('proxy_file')) {
    if (count($proxies) === 1 && $proxies[0] === false) {
        $proxies = [];
    }
    CLI::print("loading proxy file: {$proxy_file_path}");
    if (!is_readable($proxy_file_path)) {
        CLI::exit(-1, "failed to read file: {$proxy_file_path}");
    }
    if (!$proxy_file_lines = Func::file($proxy_file_path)) {
        CLI::exit(-1, "no lines loaded: {$proxy_file_path}");
    }
    foreach ($proxy_file_lines as $proxy) {
        $proxy = trim($proxy);
        if (!in_array($proxy, $proxies)) {
            $proxies[] = $proxy;
        } else {
            CLI::debug("duplicate proxy: {$proxy}");
        }
    }
}

// Load socks5 file
if ($socks5_file_path = $arg->get('socks5_file')) {
    if (count($proxies) === 1 && $proxies[0] === false) {
        $proxies = [];
    }
    CLI::print("loading proxy file: {$socks5_file_path}");
    if (!is_readable($socks5_file_path)) {
        CLI::exit(-1, "failed to read file: {$socks5_file_path}");
    }
    if (!$socks5_file_lines = Func::file($socks5_file_path)) {
        CLI::exit(-1, "no lines loaded: {$socks5_file_path}");
    }
    foreach ($socks5_file_lines as $socks5) {
        $socks5 = 'socks5://' . trim($socks5);
        if (!in_array($socks5, $proxies)) {
            $proxies[] = $socks5;
        } else {
            CLI::debug("duplicate socks5: {$socks5}");
        }
    }
}

// Load proxy strings
if ($proxy_strings = $arg->get('proxy_string')) {
    if (count($proxies) === 1 && $proxies[0] === false) {
        $proxies = [];
    }
    foreach (explode(",", $proxy_strings) as $proxy_string) {
        if (!in_array($proxy_string, $proxies, true)) {
            $proxies[] = $proxy_string;
        } else {
            CLI::debug("duplicate proxy: {$proxy_string}");
        }
    }
}

// Count proxies
$proxy_count = count($proxies);
if ($proxy_count < 1) {
    CLI::exit(-1, "no proxies were loaded");
} else {
    if ($proxy_count === 1 && $proxies[0] === false) {
        CLI::print("no proxies loaded");
    } else {
        CLI::print("proxies loaded: {$proxy_count}");
    }
}

// Load useragent file
if ($useragent_file_path = $arg->get('useragent_file')) {
    if (count($useragents) === 1 && $useragents[0] === false) {
        $useragents = [];
    }
    CLI::print("loading proxy file: {$useragent_file_path}");
    if (!is_readable($useragent_file_path)) {
        CLI::exit(-1, "file is not readable: {$useragent_file_path}");
    }
    if (!$useragent_lines = Func::file($useragent_file_path)) {
        CLI::exit(-1, "no lines loaded: {$useragent_file_path}");
    }
    foreach ($useragent_lines as $string) {
        $string = trim($string);
        if (!in_array($string, $useragents)) {
            $useragents[] = $string;
        } else {
            CLI::debug("duplicate useragent: {$string}");
        }
    }
    CLI::print("useragents: " . count($useragents));
}

// Data delimiter
if ($delimiter_data = $arg->get('data_delimiter')) {
    $data_delimiter = $delimiter_data;
}

// Output [Success] handle
if ($output_file_path = $arg->get('output_file')) {
    CLI::debug("opening output file handle: {$output_file_path}");
    if (!$output_handle = fopen($output_file_path, 'a')) {
        CLI::exit(-1, "failed to open output handle: {$output_file_path}");
    }
}

// Output [Partial] handle
if ($output_partial_file_path = $arg->get('output_partial_file')) {
    CLI::debug("opening output partial file handle: {$output_partial_file_path}");
    if (!$output_partial_handle = fopen($output_partial_file_path, 'a')) {
        CLI::exit(-1, "failed to open output (partial) handle: {$output_partial_file_path}");
    }
}

// Thread count
$threadcount = 1;
if ($threadcount_arg = $arg->get('threadcount')) {
    $threadcount_number = preg_replace('/[^0-9]/', '', $threadcount_arg);
    if (!is_numeric($threadcount_number) || intval($threadcount_number) < 1) {
        CLI::exit(-1, "invalid amount of threads");
    }

    $threadcount = intval($threadcount_number);

    if ($threadcount > $input_count) {
        $threadcount = $input_count;
        CLI::debug("threadcount lowered to match the amount of inputs");
    }
}
CLI::print("threads: {$threadcount}");

// Show action
CLI::print("starting brute process...");

 // Create threadpool
 try {
     CLI::debug("creating threadpool...");
     $threadpool = new \Pool($threadcount, Worker::class, [
        &$module_factory, $proxies, $useragents, $output_handle, $output_partial_handle, $arg->settings, $run_once_data, $data_delimiter
    ]);
 } catch (\Exception $ex) {
     CLI::print($ex->getMessage());
 }

 // Add jobs to the pool
foreach ($inputs as $input_data) {
    try {
        $threadpool->submit(
            new Threaded(
                $input_data,
                (!empty($input_data['_original_input'])) ? $input_data['_original_input'] : null
            )
        );
    } catch (\Exception $ex) {
        CLI::exit(-1, $ex->getMessage());
    }
}

// Collect & shutdown
$threadpool->collect();
$threadpool->shutdown();

CLI::print("COMPLETE");
