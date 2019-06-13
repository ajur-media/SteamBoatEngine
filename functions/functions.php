<?php
/**
 * Created 2019-06-07
 */

use Arris\AppLogger;
use SteamBoat\BBParser;

interface SteamBoatFunctions {
    function getEngineVersion():array;

    function create_BBParser($text, $mode = "posts", $youtube_enabled = false):string;
    function rewrite_hrefs_to_blank(string $text):string;

    function ddd(...$args);
    function dd($value);
    function d($value);

    function intdiv($p, $q):int;

    function pluralForm($number, $forms, string $glue = '|'):string;

    function convertUTF16E_to_UTF8(string $t):string;

    function redirect($uri, $redir = false, $code = 302);

    function logSiteUsage(string $scope, string $value);
    function logCronMessage($message = '', $mode = 'notice', ...$args);

    function getimagepath($type = "photos", $cdate = null):string;

    function logReport(string $filename, string $message);
}


if (!function_exists('getEngineVersion')) {

    /**
     * Загружает версию движка из GIT
     *
     * @return array
     */
    function getEngineVersion():array
    {
        $version_file = getenv('INSTALL_PATH') . getenv('VERSION_FILE');
        $version = [
            'date'      =>  date_format( date_create(), 'r'),
            'user'      => 'local',
            'summary'   => 'latest'
        ];

        if (getenv('VERSION')) {
            $version['summary'] = getenv('VERSION');
        } elseif (is_readable($version_file)) {
            $array = file($version_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $version = [
                'date'      => $array[1],
                'user'      => 'local',
                'summary'   => $array[0]
            ];
        }

        return $version;
    }
}

if (!function_exists('create_BBParser')) {
    /**
     * BB Parsing method
     * Используется ТОЛЬКО для юзерконтента
     *
     * @param $text
     * @param string $mode
     * @param bool $youtube_enabled
     * @return string|string[]|null
     */
    function create_BBParser($text, $mode = "posts", $youtube_enabled = false):string
    {
        $sizes = array(
            "posts" => array(560, 340),
            "comments" => array(320, 205),
        );

        $bbparsersizes = $sizes[$mode];

        if (getenv('DEBUG_LOG_BBPARSER')) AppLogger::scope('main')->debug('BBParser | input data', [$text]);

        $parser = new BBParser();
        $parser->setText($text);
        $parser->parse();
        $text = $parser->getParsed();

        if (getenv('DEBUG_LOG_BBPARSER')) AppLogger::scope('main')->debug('BBParser | getParsed', [$text]);

        $text = preg_replace("/(\-\s)/i", "&mdash; ", $text);
        $text = preg_replace("/(\s\-\s)/i", " &mdash; ", $text);

        if (getenv('DEBUG_LOG_BBPARSER')) AppLogger::scope('main')->debug('BBParser | mdash replacement', [$text]);


        if ($youtube_enabled) {
            $text = preg_replace_callback("/\[\youtube](.*)\[\/youtube\]/i", function ($matches) use ($bbparsersizes) {
                $matches = parse_url($matches[1]);
                if (!preg_match("/v=([A-Za-z0-9\_\-]{11})/i", $matches["query"], $res)) {
                    if (!preg_match("/([A-Za-z0-9\_\-]{11})/i", $matches["fragment"], $res)) {
                        return false;
                    }
                }
                $matches = $res;
                return '
<div class="video-youtube">
    <object width="' . $bbparsersizes[0] . '" height="' . $bbparsersizes[1] . '">
        <param name="wmode" value="opaque" />
        <param name="movie" value="http://www.youtube.com/v/' . $matches[1] . '?fs=1&amp;hl=ru_RU&amp;rel=0&amp;color1=0x5d1719&amp;color2=0xcd311b">
        <param name="allowFullScreen" value="true">
        <param name="allowscriptaccess" value="always">
        <embed src="http://www.youtube.com/v/' . $matches[1] . '?fs=1&amp;hl=ru_RU&amp;rel=0&amp;color1=0x5d1719&amp;color2=0xcd311b" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $bbparsersizes[0] . '" height="' . $bbparsersizes[1] . '" wmode="opaque">
    </object>
</div>';
            }, $text);
        } // if

        if (getenv('DEBUG_LOG_BBPARSER')) AppLogger::scope('main')->debug('BBParser | after youtube check', [$text]);

        $text = preg_replace_callback("/([\(]{3,})/i", function ($m) {
            return "((( ";
        }, $text);
        $text = preg_replace_callback("/([\)]{3,})/i", function ($m) {
            return ")))";
        }, $text);
        $text = preg_replace_callback("/([\!]{3,})/i", function ($m) {
            return "!!!";
        }, $text);
        $text = preg_replace_callback("/([\?]{3,})/i", function ($m) {
            return "???";
        }, $text);

        if (getenv('DEBUG_LOG_BBPARSER')) AppLogger::scope('main')->debug('BBParser | ()!? check', [$text]);

        return $text;
    }
} // create_BBParser

if (!function_exists('rewrite_hrefs_to_blank')) {

    /**
     *
     * @param $text
     * @return string|string[]|null
     */
    function rewrite_hrefs_to_blank(string $text):string
    {
        return preg_replace_callback("/<a([^>]+)>(.*?)<\/a>/i", function ($matches) {
            $matches[1] = trim($matches[1]);
            $arr = [];

            $matches[1] = preg_replace("/([\"']{0,})\s([a-zA-Z]+\=(\"|'))/i", "$1-!break!-$2", $matches[1]);
            $matches[1] = explode("-!break!-", $matches[1]);

            // предполагаем, что по-умолчанию у всех ссылок нужно ставить target=_blank
            $blank = true;
            foreach ($matches[1] as $v) {
                $r = explode("=", $v, 2);
                $r[1] = trim($r[1], "'");
                $r[1] = trim($r[1], '"');
                $arr[$r[0]] = $r[1];
                if ($r[0] == "href") {
                    // условия, исключающие установку target=_blank
                    if (!preg_match("/([a-zA-Z]+)\:\/\/(.*)/i", $r[1])) {
                        // ссылка не начинается с какого-либо протокола, соот-но она внутренняя и новое окно не нужно
                        $blank = false;
                    } else {
                        // ссылка внешняя, и нам надо понять, не ведет ли она в наш же домен
                        if (stristr($r[1], getenv('DOMAIN_FQDN'))) $blank = false;
                    }
                }
            }
            // если уже есть в списке target, то ничего не делаем
            foreach ($arr as $k => $v) {
                if ($k == "blank") $blank = false;
            }
            if ($blank) $arr['target'] = "_blank";
            $prms = array();
            foreach ($arr as $k => $v) {
                $prms[] = "{$k}=\"{$v}\"";
            }
            $params_as_string = implode(" ", $prms);
            return "<a {$params_as_string}>{$matches[2]}</a>";
        }, $text);

    }
}

if (!function_exists('ddd')) {
    /**
     * Dump many args and die
     * @param mixed ...$args
     */
    function ddd(...$args)
    {
        if (php_sapi_name() !== "cli") echo '<pre>';
        foreach (func_get_args() as $arg) {
            var_dump($arg);
        }
        if (php_sapi_name() !== "cli") echo '</pre>';
        die;
    }
}

if (!function_exists('d')) {
    /**
     * @param $value
     */
    function d($value)
    {
        if (php_sapi_name() !== "cli") echo '<pre>';
        /*foreach (func_get_args() as $arg) {
            var_dump($value);
        }*/
        var_dump($value);
        if (php_sapi_name() !== "cli") echo '</pre>';
    }
} // d

if (!function_exists('dd')) {
    /**
     * @param $value
     */
    function dd($value)
    {
        d($value);
        die;
    }
} // dd

if (!function_exists('intdiv')) {
    /**
     * intdiv() for PHP pre 7.0
     *
     * @param $p
     * @param $q
     * @return int
     */
    function intdiv($p, $q):int
    {
        return (int)floor(abs($p / $q));
    }
}

if (!function_exists('pluralForm')) {
    /**
     *
     * @param $number
     * @param array $forms (array or string with glues, x|y|z or [x,y,z]
     * @param string $glue
     * @return string
     */
    function pluralForm($number, $forms, string $glue = '|'):string
    {
        if (is_string($forms)) {
            $forms = explode($forms, $glue);
        } elseif (!is_array($forms)) {
            return null;
        }

        if (count($forms) != 3) return null;

        return
            $number % 10 == 1 && $number % 100 != 11 ?
                $forms[0] :
                ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20)
                    ? $forms[1]
                    : $forms[2]
                );
    }
}

if (!function_exists('convertUTF16E_to_UTF8')) {
    /**
     * Эта кодировка называется ISO-8859-1 и для неё есть штатные механизмы
     * https://secure.php.net/manual/ru/function.utf8-decode.php
     * и
     * https://secure.php.net/manual/ru/function.utf8-encode.php
     */


    /**
     * Переименовываем в convertUTF16E_to_UTF8
     *
     * @param $t
     * @return string|string[]|null
     */
    function convertUTF16E_to_UTF8(string $t):string
    {
        // return $t;
        /*return preg_replace_callback('#%u([0-9A-F]{4})#s', function ($match) {
            return iconv("UTF-16E", 'UTF-8', pack('H4', $match[1]));
        }, $t);*/

        return preg_replace_callback('#%u([0-9A-F]{4})#s', function () {
            iconv("UTF-16BE", "UTF-8", pack("H4", "$1"));
        }, $t);
    }

}

if (!function_exists('redirect')) {

    /**
     * @param $uri
     * @param bool $redir
     * @param int $code
     */
    function redirect($uri, $redir = false, $code = 302)
    {
        $default_scheme = getenv('REDIRECT_DEFAULT_SCHEME') ?: 'http://';

        if (strstr($uri, "http://") or strstr($uri, "https://")) {
            header("Location: " . $uri, $redir, $code);
        } else {
            header("Location: {$default_scheme}{$_SERVER['HTTP_HOST']}{$uri}", $redir, $code);
        }
    }
}

if (!function_exists('logSiteUsage')) {

    /**
     * @param $scope
     * @param $value
     */
    function logSiteUsage(string $scope, string $value)
    {
        if (getenv('DEBUG_LOG_SITEUSAGE')) AppLogger::scope($scope)->notice("Usage: ", [
            round(microtime(true) - $_SERVER['REQUEST_TIME'], 3),
            memory_get_usage(),
            $value,
            $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
        ]);
    }
}

if (!function_exists('logCronMessage')) {

    /**
     * Функция логгирования, применяемая в крон-скриптах.
     *
     * Выводит сообщение в консоль, если скрипту передан параметр `-v` ($options['verbose'] IS true)
     * И
     * логгирует его в монолог
     *
     * @param string $message
     * @param string $mode
     * @param mixed ...$args
     */
    function logCronMessage($message = '', $mode = 'notice', ...$args)
    {
        global $options;

        if (strlen(trim($message)) > 0 && ($options['verbose'] || $mode === 'error'))
            echo $message, PHP_EOL;

        if (strlen(trim($message)) == 0) {
            \Arris\AppLogger::scope('main')->{$mode}($message);
        } else {
            \Arris\AppLogger::scope('main')->{$mode}($message, $args);
        }
    }
}

if (!function_exists('getimagepath')) {

    /**
     *
     *
     * @param string $type
     * @param null $cdate
     * @return string
     */
    function getimagepath($type = "photos", $cdate = null):string
    {
        $directory_separator = DIRECTORY_SEPARATOR;

        $cdate = is_null($cdate) ? time() : strtotime($cdate);

        $path
            = getenv('INSTALL_PATH')
            . "www/i/"
            . $type
            . DIRECTORY_SEPARATOR
            . date("Y{$directory_separator}m", $cdate)
            . DIRECTORY_SEPARATOR;

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }
}

if (!function_exists('logReport')) {
    /**
     * @param $filename
     * @param $message
     */
    function logReport(string $filename, string $message)
    {
        $f = fopen($filename, 'a+');
        fwrite($f, $message);
        fclose($f);
    }
}