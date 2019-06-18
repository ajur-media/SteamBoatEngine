<?php

namespace SteamBoat;

use function array_diff;
use Arris\AppLogger;
use Exception;
use function is_dir;
use function rmdir;
use function scandir;
use function unlink;

interface SBEngineInterface
{
    public static function init(array $options);

    public static function engine_prepare_classes(array $folders): array;

    public static function engine_class_loader(string $class);

    public static function clear_nginx_cache(string $url, string $levels = '1:2'): bool;

    public static function getContentPath(string $type = "photos", string $creation_date = ''): string;

    public static function getContentURL(string $type = "photos", $creation_date = '', bool $final_slash = true): string;

    public static function loadCurrencies(): array;

    public static function rmdir($directory): bool;
}

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
     * @param array $options
     */
    /**
     *
     * @param array $options
     */
    public static function init(array $options)
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
            : getenv('INSTALL_PATH') . getenv('FILE_CURRENCY');

        self::$options['FILE_WEATHER']
            = array_key_exists('FILE_WEATHER', $options)
            ? $options['FILE_WEATHER']
            : getenv('INSTALL_PATH') . getenv('FILE_WEATHER');

        self::$storages
            = array_key_exists('STORAGE', $options)
            ? $options['STORAGE']
            : [];
    }

    /**
     * Возвращает подготовленный массив классов для переменной $CONFIG['classes']
     *
     * @param $folders
     * @return array
     */
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

    /**
     * Функция автолоадера классов
     *
     * @param $class
     */
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
            AppLogger::scope('main')->emergency("v2 Autoloader can't find `{$class_filename}` for {$class}.", []);
        }

    }

    /**
     * Очищает кэш NGINX
     *
     * @param string $url
     * @param string $levels
     * @return bool
     */
    public static function clear_nginx_cache(string $url, string $levels = '1:2'): bool
    {
        $unlink_status = true;

        if (getenv('NGINX_CACHE_USE') == 0) {
            return false;
        }

        if (is_null($levels)) {
            $levels = getenv('NGINX_CACHE_LEVELS');
        }

        $cache_root = rtrim(getenv('NGINX_CACHE_PATH'), DIRECTORY_SEPARATOR);

        if ($url === "/") {
            if (getenv('DEBUG_LOG_NGINX_CACHE')) {
                AppLogger::scope('main')->debug("NGINX Cache Force Cleaner: requested clean whole cache");
            }

            $dir_content = array_diff(scandir($cache_root), ['.', '..']);

            foreach ($dir_content as $subdir) {
                if (is_dir($cache_root . DIRECTORY_SEPARATOR . $subdir)) {
                    $unlink_status = $unlink_status && self::rmdir($cache_root . DIRECTORY_SEPARATOR . $subdir . '/');
                }
            }

            if (getenv('DEBUG_LOG_NGINX_CACHE')) {
                AppLogger::scope('main')->debug("NGINX Cache Force Cleaner: whole cache clean status: ", [ $cache_root, $unlink_status ]);
            }

            return $unlink_status;
        }

        $url_parts = parse_url($url);
        $url_parts['host'] = $url_parts['host'] ?? '';
        $url_parts['path'] = $url_parts['path'] ?? '';

        $cache_key = "GET|||{$url_parts['host']}|{$url_parts['path']}";
        $cache_filename = md5($cache_key);

        $levels = explode(':', $levels);

        $cache_filepath = $cache_root;

        $offset = 0;

        foreach ($levels as $i => $level) {
            $offset -= $level;
            $cache_filepath .= "/" . substr($cache_filename, $offset, $level);
        }

        $cache_filepath .= "/{$cache_filename}";

        if (file_exists($cache_filepath)) {
            if (getenv('DEBUG_LOG_NGINX_CACHE')) {
                AppLogger::scope('main')->debug("NGINX Cache Force Cleaner: cached data present: ", [ $cache_filepath ]);
            }

            $unlink_status = unlink($cache_filepath);

        } else {
            if (getenv('DEBUG_LOG_NGINX_CACHE')) {
                AppLogger::scope('main')->debug("NGINX Cache Force Cleaner: cached data not found: ", [ $cache_filepath ]);
            }

            $unlink_status = true;
        }

        if (getenv('DEBUG_LOG_NGINX_CACHE')) {
            AppLogger::scope('main')->debug("NGINX Cache Force Cleaner: Clear status (key/status)", [$cache_key, $unlink_status]);
        }

        return $unlink_status;
    }

    /**
     * Рекурсивно удаляет каталоги по указанному пути
     *
     * @param $directory
     * @return bool
     */
    public static function rmdir($directory): bool
    {
        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$directory/$file"))
                ? self::rmdir("$directory/$file")
                : unlink("$directory/$file");
        }
        return rmdir($directory);
    }

    /**
     * Генерирует веб-путь к картинке (упрощенный механизм, для отрисовки фронта)
     *
     * @param string $type
     * @param string $creation_date
     * @param bool $final_slash
     * @return string
     */
    public static function getContentURL(string $type = "photos", $creation_date = '', bool $final_slash = true): string
    {
        $directory_separator = DIRECTORY_SEPARATOR;
        $cdate = empty($creation_date) ? time() : strtotime($creation_date);

        $path = "/{$type}/" . date("Y{$directory_separator}m", $cdate);
        $path .= $final_slash ? '/' : '';

        return $path;
    }

    /**
     * Возвращает внутренний путь к контенту
     *
     * @param string $type
     * @param string $creation_date
     * @return string
     */
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

    /**
     * Загружает валюты из JSON-файла
     *
     * @return array
     */
    public static function loadCurrencies(): array
    {
        $MAX_CURRENCY_STRING_LENGTH = 5;

        $file_currencies = getenv('INSTALL_PATH') . getenv('FILE_CURRENCY');

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
            AppLogger::scope('main')->error('[ERROR] Load Currency ', [$e->getMessage()]);
        }

        return $current_currency;
    }


}