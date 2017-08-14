<?php

namespace phpBrute;

class Func
{
    public static function file(string $filepath)
    {
        return @file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    public static function formatInput(string $input, string $input_format, bool $add_original_input = true)
    {
        // TODO: allow a wildcard * at end and beggining of the input format
        $return = [];
        $input_format = str_replace(['\<', '\>'], ['%SIGN_OPEN%', '%SIGN_CLOSE%'], $input_format);

        if (preg_match_all('/<(.+?)>/', $input_format, $matches)) {
            $indexes = [];

            foreach ($matches[1] as $index => $var_name) {
                $indexes[$var_name] = $index + 1;
                $capture_string = ($index != (count($matches[1]) - 1)) ? '(.+?)' : '(.+)';
                $input_format = str_replace("<{$var_name}>", $capture_string, $input_format);
            }

            if (preg_match('/' . str_replace(['%SIGN_OPEN%', '%SIGN_CLOSE%'], ['<', '>'], $input_format) . '/', $input, $matches)) {
                if ($add_original_input) {
                    $return = ['_original_input' => $input];
                }
                foreach ($indexes as $var_name => $index) {
                    $return[$var_name] = $matches[$index];
                }
            }
        }

        return $return;
    }
}
