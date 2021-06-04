<?php

namespace SteamBoat;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class SBEngine
 * @package SteamBoat
 *
 * Использует уровни логгирования:
 * - emergency - фатальная ошибка инициализации
 * - error - ошибка загрузки данных
 *
 */
class SBEngine implements SBEngineInterface, SBEngineConstants
{
    const VERSION = '1.30.0';

    public static $options = [
        'PROJECT_PUBLIC'    =>  '',
        'PROJECT_STORAGE'   =>  '',
        'PROJECT_CLASSES'   =>  '',
    ];

    // алиасы к папкам хранилища
    public static $storages = [

    ];

    /**
     * @var LoggerInterface $_logger
     */
    public static $_logger;

    public static function init(array $options, LoggerInterface $logger = null)
    {
        if (!array_key_exists('PROJECT_PUBLIC', $options)) {
            die('SBEngine::init() option [PROJECT_PUBLIC] not present!');
        }

        self::$options['PROJECT_PUBLIC'] = $options['PROJECT_PUBLIC'];

        self::$options['PROJECT_STORAGE']
            = array_key_exists('PROJECT_STORAGE', $options)
            ? $options['PROJECT_PUBLIC'] . $options['PROJECT_STORAGE']
            : $options['PROJECT_PUBLIC'] . "i/";

        self::$options['PROJECT_CLASSES']
            = array_key_exists('PROJECT_CLASSES', $options)
            ? $options['PROJECT_PUBLIC'] . $options['PROJECT_CLASSES']
            : $options['PROJECT_PUBLIC'] . "engine.legacy/";

        self::$storages
            = array_key_exists('STORAGE', $options)
            ? $options['STORAGE']
            : [];
        
        self::$options['LOG_SITE_USAGE']
            = array_key_exists('LOG_SITE_USAGE', $options)
            ? $options['LOG_SITE_USAGE']
            : false;

        self::$_logger
            = $logger instanceof LoggerInterface
            ? $logger
            : new NullLogger();
    }

    public static function engine_prepare_classes(array $folders): array
    {
        $engine_path = self::$options['PROJECT_CLASSES'];
        $classes_list = [];

        foreach ($folders as $type => $class_prefix) {
            $directory = $engine_path . $type;
            $list = scandir($directory);
            $list = array_diff($list, ['.', '..']);

            foreach ($list as $a_file) {
                if (is_readable($directory . DIRECTORY_SEPARATOR . $a_file)) {
                    $class_name = explode('.', $a_file)[0];

                    $class_name = str_replace($class_prefix, '', $class_name);

                    $classes_list[$type][] = $class_name;
                }
            }
        }
        return $classes_list;
    }

    public static function engine_class_loader(string $class)
    {
        $classes_directory = self::$options['PROJECT_CLASSES'];
        $class_words = explode("_", $class);

        if (count($class_words) === 3) {

            // ajax or request классы
            $class_filename = "{$class_words[1]}.{$class_words[0]}" . DIRECTORY_SEPARATOR . $class . '.php';

        } elseif (count($class_words) === 2) {

            // common or site/get or admin/get classes
            $class_filename = $class_words[0] . DIRECTORY_SEPARATOR . $class . '.php';
        }

        $class_filename = $classes_directory . $class_filename;

        if (is_file($class_filename) && is_readable($class_filename)) {
            include($class_filename);
        } else {
            self::$_logger->emergency("v2 Autoloader can't find `{$class_filename}` for {$class}.", []);
        }

    }

    public static function getContentURL(string $type = "photos", $creation_date = '', bool $final_slash = true): string
    {
        $directory_separator = DIRECTORY_SEPARATOR;
        $cdate = empty($creation_date) ? time() : strtotime($creation_date);

        $path = "/{$type}/" . date("Y{$directory_separator}m", $cdate);
        $path .= $final_slash ? '/' : '';

        return $path;
    }

    public static function getContentPath(string $type = "photos", string $creation_date = ''): string
    {
        $STORAGE_FOLDER = self::$options['PROJECT_STORAGE'];

        $directory_separator = DIRECTORY_SEPARATOR;

        $creation_date = empty($creation_date) ? time() : strtotime($creation_date);

        if (!in_array($type, self::$storages)) {
            return "/tmp/";
        }

        $path
            = $STORAGE_FOLDER
            . self::$storages[$type]
            . DIRECTORY_SEPARATOR
            . date("Y{$directory_separator}m", $creation_date)
            . DIRECTORY_SEPARATOR;

        if (!is_dir($path)) {
            if (!mkdir( $path, 0777, true ) && !is_dir( $path )) {
                throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $path ) );
            }
        }

        return $path;
    }

    public static function parseUploadError(array $upload_data, $where = __METHOD__):string
    {
        switch ($upload_data['error']) {
            case UPLOAD_ERR_OK: {
                $error = '0 UPLOAD_ERR_OK: Файл успешно загружен на сервер, но что-то пошло не так.';
                break;
            }
            case UPLOAD_ERR_NO_FILE: {
                $error = 'UPLOAD_ERR_NO_FILE: Файл не был загружен по неизвестной причине';
                break;
            }
            case UPLOAD_ERR_INI_SIZE: {
                $error = 'UPLOAD_ERR_INI_SIZE: Размер принятого файла превысил upload_max_filesize в php.ini';
                break;
            }
            case UPLOAD_ERR_FORM_SIZE: {
                $error = 'UPLOAD_ERR_FORM_SIZE: Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме.';
                break;
            }
            case UPLOAD_ERR_PARTIAL: {
                $error = 'UPLOAD_ERR_PARTIAL: Загружаемый файл был получен только частично. ';
                break;
            }
            default: {
                $error = '?: Что-то пошло не так.';
            }
        }

        if (getenv('LOGGING.ADMIN_FILEUPLOAD')) {
            self::$_logger->error("{$where} throw file upload error:", [ $error ]);
        }

        return $error;
    }

    public static function is_ssl():bool
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

    public static function getRandomFilename(int $length = 20, string $suffix = '', $prefix_format = 'Ymd'):string
    {
        $dictionary = self::DICTIONARY;
        $dictionary_len = strlen($dictionary);

        // если суффикс не NULL, то _суффикс иначе пустая строка
        $suffix = !empty($suffix) ? '_' . $suffix : '';

        $salt = '';
        for ($i = 0; $i < $length; $i++) {
            $salt .= $dictionary[random_int(0, $dictionary_len - 1)];
        }

        return (date_format(date_create(), $prefix_format)) . '_' . $salt . $suffix;
    }

    public static function getRandomString(int $length):string
    {
        $salt = "";
        $dictionary = SBEngineConstants::DICTIONARY_FULL;
        $dictionary_len = strlen($dictionary);

        for ($i = 0; $i < $length; $i++) {
            $salt .= $dictionary[ random_int(0, $dictionary_len - 1) ];
        }

        return $salt;
    }

    public static function getEngineVersion():array
    {
        $version_file = getenv('VERSION.FILE');
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
                'date'      => $array[1] ?? time(),
                'user'      => 'local',
                'summary'   => $array[0] ?? ''
            ];
        }

        return $version;
    }

    public static function getSiteUsageMetrics(MySQLWrapper $mysql, array $config): array
    {
        return [
            'memory.usage'      =>  memory_get_usage(true),
            'memory.peak'       =>  memory_get_peak_usage(true),
            'mysql.query_count' =>  $mysql->getQueryCount(),
            'mysql.query_time'  =>  round($mysql->getQueryTime(), 3),
            'time.total'        =>  round(microtime(true) - $_SERVER['REQUEST_TIME'], 3),
            'site.routed'       =>  $config['ROUTED'] ?? '/',
            'site.url'          =>  $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
        ];
    }

    public static function logSiteUsage(LoggerInterface $logger, array $metrics, bool $is_print = false)
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

        if (self::$options['LOG_SITE_USAGE']) {
            unset($metrics['time.start']);
            unset($metrics['time.end']);
            $logger->notice('Metrics:', $metrics);
        }
        return true;
    }

    public static function sanitizeHTMLData($body, $bad_values = ['+', '-', '~', '(', ')', '*', '"', '>', '<'])
    {
        if (!is_array($bad_values) || empty($bad_values)) $bad_values = ['+', '-', '~', '(', ')', '*', '"', '>', '<'];
        return str_replace($bad_values, '', addslashes($body));
    }

    public static function normalizeSerialData(&$data, array $default_value = [])
    {
        $data = empty($data) ? $default_value : @unserialize($data);
    }

    public static function simpleSendEMAIL($to, $from = "", $subject = "", $message = "", $fromname = "")
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

    public static function unEscapeString($input)
    {
        $escape_chars = "0410 0430 0411 0431 0412 0432 0413 0433 0490 0491 0414 0434 0415 0435 0401 0451 0404 0454 0416 0436 0417 0437 0418 0438 0406 0456 0419 0439 041A 043A 041B 043B 041C 043C 041D 043D 041E 043E 041F 043F 0420 0440 0421 0441 0422 0442 0423 0443 0424 0444 0425 0445 0426 0446 0427 0447 0428 0448 0429 0449 042A 044A 042B 044B 042C 044C 042D 044D 042E 044E 042F 044F";
        $russian_chars = "А а Б б В в Г г Ґ ґ Д д Е е Ё ё Є є Ж ж З з И и І і Й й К к Л л М м Н н О о П п Р р С с Т т У у Ф ф Х х Ц ц Ч ч Ш ш Щ щ Ъ ъ Ы ы Ь ь Э э Ю ю Я я";

        $e = explode(" ", $escape_chars);
        $r = explode(" ", $russian_chars);
        $rus_array = explode("%u", $input);
    
        $new_word = str_replace( array( $e, "%20" ), array( $r, " " ), $rus_array );

        return (implode("", $new_word));
    }
    
    public static function setOption(array $options = [], $key = null, $default_value = null)
    {
        if (!is_array($options)) return $default_value;

        if (is_null($key)) return $default_value;

        return array_key_exists($key, $options) ? $options[ $key ] : $default_value;
    }
}

# -eof-
