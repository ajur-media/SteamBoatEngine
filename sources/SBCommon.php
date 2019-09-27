<?php

namespace SteamBoat;

/**
 * Класс статических методов движка
 *
 * Class SBCommon
 * @package SteamBoat
 *
 * Логгирование не используется
 */
class SBCommon implements SBCommonInterface
{
    const VERSION = '1.23';

    const DICTIONARY_FULL = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz';

    const DICTIONARY = '0123456789abcdefghijklmnopqrstuvwxyz';

    public static function getRandomString(int $length):string
    {
        $salt = "";
        $dictionary = self::DICTIONARY_FULL;
        $dictionary_len = strlen($dictionary);

        for ($i = 0; $i < $length; $i++) {
            $salt .= $dictionary[ mt_rand(0, $dictionary_len - 1) ];
        }

        return $salt;
    }

    public static function getRandomFilename(int $length = 20, string $suffix = '', $prefix_format = 'Ymd'):string
    {
        $dictionary = self::DICTIONARY;
        $dictionary_len = strlen($dictionary);

        // если суффикс не NULL, то _суффикс иначе пустая строка
        $suffix = !empty($suffix) ? '_' . $suffix : '';

        $salt = '';
        for ($i = 0; $i < $length; $i++) {
            $salt .= $dictionary[mt_rand(0, $dictionary_len - 1)];
        }

        return (date_format(date_create(), $prefix_format)) . '_' . $salt . $suffix;
    }

    public static function redirectCode(string $uri, bool $replace_prev_headers = false, int $code = 302)
    {
        $scheme = (self::is_ssl() ? "https://" : "http://");
        $code = array_key_exists($code, self::HTTP_CODES) ? self::HTTP_CODES[$code] : self::HTTP_CODES[302];

        header($code);

        if (strstr($uri, "http://") or strstr($uri, "https://")) {
            header("Location: " . $uri, $replace_prev_headers, $code);
        } else {
            header("Location: {$scheme}" . $_SERVER['HTTP_HOST'] . $uri, $replace_prev_headers, $code);
        }
        exit(0);
    }

    public static function is_ssl()
    {
        if (isset($_SERVER['HTTPS'])) {
            if ('on' == strtolower($_SERVER['HTTPS']))
                return true;
            if ('1' == $_SERVER['HTTPS'])
                return true;
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        }
        return false;
    }


} # -eof-