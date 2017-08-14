<?php
namespace phpBrute\Module;

use \phpBrute\Helper;

class DeathByCaptcha extends \phpBrute\Module
{
    public $info = [
        'name' => 'DeathByCaptcha Example',
        'author' => 'Yani',
        'version' => 1,
        'info' => 'Use filepaths as the inputs',
        'input_format' => '<filepath>',
        'settings' => [
            'dbc-user' => [true, 'DeathByCaptcha username'],
            'dbc-pass' => [true, 'DeathByCaptcha password']
        ]
    ];

    public function runOnce(array $settings = [])
    {
        if (empty($settings['dbc-user']) || empty($settings['dbc-pass'])) {
            throw new \Exception("dbc-user and dbc-pass settings are required");
        }

        try {
            if (($balance = Helper::deathByCaptchaBalance($settings['dbc-user'], $settings['dbc-pass'])) > 0) {
                $this->print("DeathByCaptcha balance: \${$balance}");
            } else {
                throw new \Exception("not enough DBC balance");
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    public function run($data, $proxy, $useragent, array $settings = [], array $run_once_data = [])
    {
        if ($captcha = Helper::deathByCaptcha($data['filepath'], $settings['dbc-user'], $settings['dbc-pass'])) {
            return $this->return(self::SUCCESS, [$data['filepath'], $captcha], $data['filepath'] . ': ' . $captcha);
        }

        return $this->return(self::INVALID);
    }
}
