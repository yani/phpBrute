<?php

namespace phpBrute;

class CLI
{
    const cli_text_colors = [
        'black'       => '0;30',
        'dark-red'    => '0;31',
        'green'       => '0;32',
        'brown'       => '0;33',
        'dark-blue'   => '0;34',
        'purple'      => '0;35',
        'dark-cyan'   => '0;36',
        'light-gray'  => '0;37',
        'dark-gray'   => '1;30',
        'red'         => '1;31',
        'light-red'   => '1;31',
        'lime'        => '1;32',
        'light-green' => '1;32',
        'yellow'      => '1;33',
        'blue'        => '1;34',
        'magenta'     => '1;35',
        'cyan'        => '1;36',
        'light-cyan'  => '1;36',
        'white'       => '1;37',
    ];

    const cli_bg_colors = [
        'black'      => '40',
        'red'        => '41',
        'green'      => '42',
        'yellow'     => '43',
        'blue'       => '44',
        'magenta'    => '45',
        'cyan'       => '46',
        'white'      => '47',
        'gray'       => '47',
        'light-gray' => '47',
    ];

    public static function color(string $string, string $text_color, $bg_color = null, bool $underline = false)
    {
        if (!defined('PHPBRUTE_COLOR') || !PHPBRUTE_COLOR) {
            return $string;
        }

        if (!empty(self::cli_text_colors[$text_color])) {
            $flags[] = self::cli_text_colors[$text_color];
        } else {
            throw new \Exception("invalid color: {$text_color}");
        }

        if (is_string($bg_color)) {
            if (!empty(self::cli_bg_colors[$bg_color])) {
                $flags[] = self::cli_bg_colors[$bg_color];
            } else {
                throw new \Exception("invalid color: {$bg_color}");
            }
        }

        if ($underline) {
            $flags[] = '4';
        }

        return "\e[" . implode(';', $flags) . 'm' . $string . "\e[0m";
    }

    public static function print(string $string, bool $wordwrap = true)
    {
        $str = '';
        if ($wordwrap) {
            foreach (explode("\n", $string) as $line) {
                $str .= wordwrap($line, 69, PHP_EOL) . "\n";
            }
            $str = str_replace("\n", "\n" . str_repeat(' ', 11), trim($str));
        } else {
            $str = $string;
        }
        echo self::color('[' . date('H:i:s') . '] ', 'dark-gray') . $str . PHP_EOL;
    }

    public static function exit(int $exit_code, string $string)
    {
        self::print($string);
        exit($exit_code);
    }

    public static function debug(string $string)
    {
        if (defined('PHPBRUTE_DEBUG')) {
            self::print($string);
        }
    }

    public static function escape(string $string)
    {
        return str_replace(["\t", "\r", "\n"], ['\t', '\r', '\n'], $string);
    }
}
