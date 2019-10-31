<?php

namespace SteamBoat;

use Monolog\Logger;
use Arris\AppLogger;
use Exception;

use function array_diff;
use function is_dir;
use function scandir;

/**
 * Class SBEngine
 * @package SteamBoat
 *
 * Использует уровни логгирования:
 * - emergency - фатальная ошибка инициализации
 * - error - ошибка загрузки данных
 *
 */
class SBEngine implements SBEngineInterface
{
    const VERSION = '1.22';

    public static $options = [
        'PROJECT_PUBLIC'    =>  '',
        'PROJECT_STORAGE'   =>  '',
        'PROJECT_CLASSES'   =>  '',
        //
        'FILE_CURRENCY'     =>  '',
        'FILE_WEATHER'      =>  '',
        //
    ];

    // алиасы к папкам хранилища
    public static $storages = [];

    /**
     * @var Logger $_logger
     */
    public static $_logger;

    public static function init(array $options, $logger = null)
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

        self::$options['FILE_CURRENCY']
            = array_key_exists('FILE_CURRENCY', $options)
            ? $options['FILE_CURRENCY']
            : getenv('FILE.CURRENCY');

        self::$options['FILE_WEATHER']
            = array_key_exists('FILE_WEATHER', $options)
            ? $options['FILE_WEATHER']
            : getenv('FILE.WEATHER');

        self::$storages
            = array_key_exists('STORAGE', $options)
            ? $options['STORAGE']
            : [];

        self::$_logger
            = $logger instanceof Logger
            ? $logger
            : AppLogger::addNullLogger();
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
            mkdir($path, 0777, true);
        }

        return $path;
    }

    public static function loadCurrencies(): array
    {
        $MAX_CURRENCY_STRING_LENGTH = 5;

        $file_currencies = self::$options['FILE_CURRENCY'];

        $current_currency = [];

        try {
            $file_content = file_get_contents($file_currencies);
            if ($file_content === FALSE) throw new Exception("Currency file `{$file_currencies}` not found", 1);

            $file_content = json_decode($file_content, true);
            if (($file_content === NULL) || !is_array($file_content)) throw new Exception("Currency data can't be parsed", 2);

            if (!array_key_exists('data', $file_content)) throw new Exception("Currency file does not contain DATA section", 3);

            // добиваем валюту до $MAX_CURRENCY_STRING_LENGTH нулями (то есть 55.4 (4 десятых) добивается до 55.40 (40 копеек)
            foreach ($file_content['data'] as $currency_code => $currency_data) {
                $current_currency[$currency_code] = str_pad($currency_data, $MAX_CURRENCY_STRING_LENGTH, '0');
            }

        } catch (Exception $e) {
            self::$_logger->error('[ERROR] Load Currency ', [$e->getMessage()]);
        }

        return $current_currency;
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

        if (getenv('LOGGING.ADMIN_FILEUPLOAD') && self::$_logger instanceof Logger) {
            self::$_logger->error("{$where} throw file upload error:", [ $error ]);
        }

        return $error;
    }


}