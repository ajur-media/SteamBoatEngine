<?php
/**
 * Created 2019-06-07
 *
 * Функции, вне неймспейса
 */

use Arris\AppLogger;
use Monolog\Logger;
use SteamBoat\BBParser;

interface SteamBoatFunctions {
    const VERSION = '1.20.0';

    function getEngineVersion():array;

    function convert_BB_to_HTML($text, $mode = "posts", $youtube_enabled = false):string;
    function rewrite_hrefs_to_blank(string $text):string;

    function intdiv($p, $q):int;

    function pluralForm($number, $forms, string $glue = '|'):string;

    function convertUTF16E_to_UTF8(string $t):string;

    function http_redirect($uri, $replace_prev_headers = false, $code = 302);

    function logSiteUsage(string $scope, string $value);
    function logCronMessage($message = '', $mode = 'notice', ...$args); //@todo: говнокод

    function getimagepath($type = "photos", $cdate = null):string;

    function logReport(string $filename, string $message);  //@todo: говнокод

    function parseUploadError(array $upload_data, $where = __METHOD__):string;

    function floatToFixedString($value, $separator = '.'):string;

    function sanitizeHTMLData($body, $bad_values = ['+', '-', '~', '(', ')', '*', '"', '>', '<']);

    function normalizeSerialData(&$data, array $default_value = []);

    function toRange($value, $min, $max);
}

if (!function_exists('getEngineVersion')) {

    /**
     * Загружает версию движка из GIT
     *
     * @return array
     */
    function getEngineVersion():array
    {
        $version_file = getenv('PATH.INSTALL') . getenv('VERSION.FILE');
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

if (!function_exists('convert_BB_to_HTML')) {
    /**
     * convert_BB_to_HTML
     *
     * BB Parsing method
     * Используется ТОЛЬКО для юзерконтента
     *
     * @param $text
     * @param string $mode
     * @param bool $youtube_enabled
     * @return string|string[]|null
     */
    function convert_BB_to_HTML($text, $mode = "posts", $youtube_enabled = false):string
    {
        if (getenv('DEBUG_LOG_BBPARSER')) {
            $LOGGER = AppLogger::scope('bbparser');
        } else {
            $LOGGER = AppLogger::addNullLogger();
        }

        $sizes = array(
            "posts" => array(560, 340),
            "comments" => array(320, 205),
        );

        $bbparsersizes = $sizes[$mode];

        $LOGGER->debug('BBParser | input data', [$text]);

        $parser = new BBParser();
        $parser->setText($text);
        $parser->parse();
        $text = $parser->getParsed();

        $LOGGER->debug('BBParser | getParsed', [$text]);

        $text = preg_replace("/(\-\s)/i", "&mdash; ", $text);
        $text = preg_replace("/(\s\-\s)/i", " &mdash; ", $text);

        $LOGGER->debug('BBParser | mdash replacement', [$text]);

        if ($youtube_enabled) {
            $text = preg_replace_callback("/\[youtube\](.*)\[\/youtube\]/i", function ($matches) use ($bbparsersizes) {
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

        $LOGGER->debug('BBParser | after youtube check', [$text]);

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

        $LOGGER->debug('BBParser | ()!? check', [$text]);

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
                        if (stristr($r[1], getenv('DOMAIN.FQDN'))) $blank = false;
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
            return '';
        }

        if (count($forms) != 3) return '';

        return
            ($number % 10 == 1 && $number % 100 != 11)
                ? $forms[0]
                : (
                    ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20))
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
        return preg_replace_callback('#%u([0-9A-F]{4})#s', function () {
            iconv("UTF-16BE", "UTF-8", pack("H4", "$1"));
        }, $t);
    }

}

if (!function_exists('getSiteUsageMetrics')) {

    /**
     * Функция сбора метрик статистики сайта
     *
     * @param \SteamBoat\MySQLWrapper $mysql
     * @param array $config
     * @return array
     */
    function getSiteUsageMetrics(\SteamBoat\MySQLWrapper $mysql, array $config)
    {
        return [
            'memory.usage'      =>  memory_get_usage(true),
            'memory.peak'       =>  memory_get_peak_usage(true),
            'mysql.query_count' =>  $mysql->mysqlcountquery,
            'mysql.query_time'  =>  round($mysql->mysqlquerytime, 3),
            'time.total'        =>  round(microtime(true) - $_SERVER['REQUEST_TIME'], 3),
            'site.routed'       =>  $config['ROUTED'],
            'site.url'          =>  $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
        ];
    }
}

if (!function_exists('logSiteUsage')) {

    /**
     * Функция логгирования собранных метрик и, возможно, печати их в поток вывода
     *
     * @param \Monolog\Logger $logger
     * @param array $metrics
     * @param bool $is_print
     * @return bool
     */
    function logSiteUsage(\Monolog\Logger $logger, array $metrics, bool $is_print = false)
    {
        if (empty($metrics)) return false;

        if ($is_print) {
            $site_usage_stats = sprintf(
                '<!-- Consumed memory: %u bytes, SQL query count: %u, SQL time %g sec, Total time: %g sec. -->',
                $metrics['memory.usage'],
                $metrics['mysql.query_count'],
                $metrics['mysql.query_time'],
                $metrics['time.total']
            );
            echo $site_usage_stats;
        }

        if (getenv('LOGGING.SITE_USAGE') && $logger instanceof \Monolog\Logger) {
            unset($metrics['time.start']);
            unset($metrics['time.end']);
            $logger->notice('Metrics:', $metrics);
        }
        return true;
    }
}


if (!function_exists('logSiteUsage_v2')) {
    /**
     * Старая функция логгирования, используется в "августовских" логах
     *
     * 47news до версии 1.24.0
     * DP до версии 2.9.0
     *
     * @param \Monolog\Logger $logger_instance
     * @param string $site_area
     * @param array $mysql_stats
     */
    function logSiteUsage_v2(\Monolog\Logger $logger_instance, string $site_area, array $mysql_stats)
    {
        if (empty($mysql_stats)) $mysql_stats = ['mysql_query_time' => null, 'mysql_query_count' => null];

        if (getenv('DEBUG_LOG_SITEUSAGE')) {
            $logger_instance->notice("Usage", [
                $_SERVER['REMOTE_ADDR'],
                'Time',
                round(microtime(true) - $_SERVER['REQUEST_TIME'], 3),
                'Memory',
                memory_get_usage(),
                'MySQL',
                [
                    'Time',
                    $mysql_stats['mysql_query_time'],
                    'Queries',
                    $mysql_stats['mysql_query_count']
                ],
                $site_area,
                $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
            ]);
        }
    }
}

if (!function_exists('logSiteUsage_v1')) {

    /**
     * Логгирование использования сайта
     * Используется на FFI включая версию 1.17.4 как logSiteUsage
     *
     * @param $scope
     * @param $value
     */
    function logSiteUsage_v1(string $scope, string $value)
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
            = getenv('PATH.INSTALL')
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

if (!function_exists('parseUploadError')) {

    /**
     *
     * @param array $upload_data
     * @param string $where
     * @return string
     */
    function parseUploadError(array $upload_data, $where = __METHOD__):string
    {
        return \SteamBoat\SBEngine::parseUploadError($upload_data, $where);
    }
}

if (!function_exists('floatToFixedString')) {
    /**
     * Форматирует float/double строку как число в строке с заданным разделителем десятичных знаков
     *
     * @param $value
     * @param string $separator (по умолчанию '.')
     * @return string
     */
    function floatToFixedString($value, $separator = '.'):string
    {
        return str_replace(',', $separator, (string)$value);
    }
}

if (!function_exists('sanitizeHTMLData')) {

    /**
     * Очищает пользовательский ввод от некорректных данных
     *
     * @param $body
     * @param array $bad_values ['+', '-', '~', '(', ')', '*', '"', '>', '<']
     * @return mixed
     */
    function sanitizeHTMLData($body, $bad_values = ['+', '-', '~', '(', ')', '*', '"', '>', '<'])
    {
        if (empty($bad_values)) $bad_values = ['+', '-', '~', '(', ')', '*', '"', '>', '<'];
        return str_replace($bad_values, '', addslashes($body));
    }
}

if (!function_exists('normalizeSerialData')) {
    /**
     * Десериализует данные или возвращает значение по умолчанию
     * в случае их "пустоты"
     *
     * @param $data
     * @param $default_value
     */
    function normalizeSerialData(&$data, array $default_value = [])
    {
        $data = empty($data) ? $default_value : @unserialize($data);
    }
}

if (!function_exists('toRange')) {
    /**
     * 
     * @param $value
     * @param $min
     * @param $max
     * @return mixed
     */
    function toRange($value, $min, $max)
    {
        return max($min, min($value, $max));
    }
}

/**
 * Doctor Piter
 */
if (!function_exists('convert_UTF16BE_to_UTF8')){
    function convert_UTF16BE_to_UTF8($t)
    {
        //return preg_replace( '#%u([0-9A-F]{1,4})#ie', "'& #'.hexdec('\\1').';'", $t );
        // return preg_replace('#%u([0-9A-F]{4})#se', 'iconv("UTF-16BE","UTF-8",pack("H4","$1"))', $t);
        return preg_replace_callback('#%u([0-9A-F]{4})#s', function (){
            iconv("UTF-16BE","UTF-8", pack("H4","$1"));
        }, $t);
    }
}

if (!function_exists('unEscapeString')){
    function unEscapeString($input)
    {
        $escape_chars = "0410 0430 0411 0431 0412 0432 0413 0433 0490 0491 0414 0434 0415 0435 0401 0451 0404 0454 0416 0436 0417 0437 0418 0438 0406 0456 0419 0439 041A 043A 041B 043B 041C 043C 041D 043D 041E 043E 041F 043F 0420 0440 0421 0441 0422 0442 0423 0443 0424 0444 0425 0445 0426 0446 0427 0447 0428 0448 0429 0449 042A 044A 042B 044B 042C 044C 042D 044D 042E 044E 042F 044F";
        $russian_chars = "А а Б б В в Г г Ґ ґ Д д Е е Ё ё Є є Ж ж З з И и І і Й й К к Л л М м Н н О о П п Р р С с Т т У у Ф ф Х х Ц ц Ч ч Ш ш Щ щ Ъ ъ Ы ы Ь ь Э э Ю ю Я я";

        $e = explode(" ", $escape_chars);
        $r = explode(" ", $russian_chars);
        $rus_array = explode("%u", $input);

        $new_word = str_replace($e, $r, $rus_array);
        $new_word = str_replace("%20", " ", $new_word);

        return (implode("", $new_word));
    }
}

/**
 * stringSmartTruncate string for DP (? === jbzoo/utils -> Str::limitChars($string, $limit = 100, $append = '...') ?
 */
if (!function_exists('stringSmartTruncate')){
    function stringSmartTruncate($string, $length = 80, $etc = "...", $break_words = false, $middle = false)
    {
        if (mb_strlen($string, "UTF-8") > $length) {
            $length -= min($length, mb_strlen($etc, "UTF-8"));
            if (!$break_words && !$middle) {
                $string = preg_replace('/\s+?(\S+)?$/i', '', mb_substr($string, 0, $length + 1, "UTF-8"));
            }
            if (!$middle) {
                return mb_substr($string, 0, $length, "UTF-8") . $etc;
            }
            return mb_substr($string, 0, $length / 2, "UTF-8") . $etc . mb_substr($string, -$length / 2, $length, "UTF-8");
        } else {
            return $string;
        }
    }

}


if (!function_exists('simpleSendEMAIL')){

    /**
     * Посылает письмо
     *
     * @param $to
     * @param string $from
     * @param string $subject
     * @param string $message
     * @param string $fromname
     * @return bool
     */
    function simpleSendEMAIL($to, $from = "", $subject = "", $message = "", $fromname = "")
    {
        global $CONFIG;

        if ($from == "") {
            $from = $CONFIG['emails']['noreply'];
        }

        $headers = [];
        if (strlen($fromname)) {
            $headers[] = "From: =?UTF-8?B?" . base64_encode($fromname) . "?= <{$from}>";
            $headers[] = "Reply-To: {$fromname} <{$from}>";
        } else {
            $headers[] = "From: {$from}";
            $headers[] = "Reply-To: {$from}";
        }
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=utf-8";

        $headers = implode("\r\n", $headers);

        return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $headers);
    }
}

if (!function_exists('smarty_modifier_html_substr')) {

    /**
    -------------------------------------------------------------
     * File: modifier.html_substr.php
     * https://stackoverflow.com/a/49094841
     * Type: modifier
     * Name: html_substr
     * Version: 1.0
     * Date: June 19th, 2003
     * Purpose: Cut a string preserving any tag nesting and matching.
     * Install: Drop into the plugin directory.
     * Author: Original Javascript Code: Benjamin Lupu <hide@address.com>
     * Translation to PHP & Smarty: Edward Dale <hide@address.com>
     * Modification to add a string: Sebastian Kuhlmann <hide@address.com>
     * Modification to put the added string before closing <p> or <li> tags by Peter Carter http://www.podhawk.com
     *
     * @param $string
     * @param $length
     * @param string $addstring
     * @return bool|string
     *
     * Same problem: https://stackoverflow.com/questions/1193500/truncate-text-containing-html-ignoring-tags
     */
    function smarty_modifier_html_substr($string, $length, $addstring = "")
    {

        //some nice italics for the add-string
        if (!empty($addstring)) $addstring = "<i> " . $addstring . "</i>";

        if (strlen($string) > $length) {
            if (!empty($string) && $length > 0) {
                $isText = true;
                $ret = "";
                $i = 0;

                $lastSpacePosition = -1;

                $tagsArray = array();
                $currentTag = "";

                $addstringAdded = false;

                $noTagLength = strlen(strip_tags($string));

                // Parser loop
                for ($j = 0; $j < strlen($string); $j++) {

                    $currentChar = substr($string, $j, 1);
                    $ret .= $currentChar;

                    // Lesser than event
                    if ($currentChar == "<") $isText = false;

                    // Character handler
                    if ($isText) {

                        // Memorize last space position
                        if ($currentChar == " ") {
                            $lastSpacePosition = $j;
                        } else {
                            $lastChar = $currentChar;
                        }

                        $i++;
                    } else {
                        $currentTag .= $currentChar;
                    }

                    // Greater than event
                    if ($currentChar == ">") {
                        $isText = true;

                        // Opening tag handler
                        if ((strpos($currentTag, "<") !== FALSE) &&
                            (strpos($currentTag, "/>") === FALSE) &&
                            (strpos($currentTag, "</") === FALSE)) {

                            // Tag has attribute(s)
                            if (strpos($currentTag, " ") !== FALSE) {
                                $currentTag = substr($currentTag, 1, strpos($currentTag, " ") - 1);
                            } else {
                                // Tag doesn't have attribute(s)
                                $currentTag = substr($currentTag, 1, -1);
                            }

                            array_push($tagsArray, $currentTag);

                        } else if (strpos($currentTag, "</") !== FALSE) {
                            array_pop($tagsArray);
                        }

                        $currentTag = "";
                    }

                    if ($i >= $length) {
                        break;
                    }
                }

                // Cut HTML string at last space position
                if ($length < $noTagLength) {
                    if ($lastSpacePosition != -1) {
                        $ret = substr($string, 0, $lastSpacePosition);
                    } else {
                        $ret = substr($string, $j);
                    }
                }

                // Close broken XHTML elements
                while (sizeof($tagsArray) != 0) {
                    $aTag = array_pop($tagsArray);
                    // if a <p> or <li> tag needs to be closed, put the add-string in first
                    if (($aTag == "p" || $aTag == "li") && strlen($string) > $length) {
                        $ret .= $addstring;
                        $addstringAdded = true;
                    }
                    $ret .= "</" . $aTag . ">\n";
                }

            } else {
                $ret = "";
            }

            // if we have not added the add-string already
            if (strlen($string) > $length && $addstringAdded == false) {
                return ($ret . $addstring);
            } else {
                return ($ret);
            }
        } else {
            return ($string);
        }
    }

}

if (!function_exists('close_tags')){

    /**
     * Вызывается в шаблонах
     * @param $content
     * @return string
     */
    function close_tags($content)
    {
        preg_match_all('#<(?!meta|em|strong|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $content, $result);
        $openedtags = $result[1];
        preg_match_all('#</([a-z]+)>#iU', $content, $result);
        $closedtags = $result[1];
        $len_opened = count($openedtags);
        if (count($closedtags) == $len_opened) {
            return $content;
        }
        $openedtags = array_reverse($openedtags);
        for ($i = 0; $i < $len_opened; $i++) {
            if (!in_array($openedtags[$i], $closedtags)) {
                $content .= '</' . $openedtags[$i] . '>';
            } else {
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }
        return $content;
    }
}


# -eof-

if (!function_exists('_')){
    function _()
    {

    }
}