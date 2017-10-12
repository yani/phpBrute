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
