<?php

namespace SteamBoat;

use Psr\Log\LoggerInterface;

interface SBEngineInterface
{
    /**
     * Инициализирует SteamBoat Engine
     *
     * @param array $options
     * @param $logger
     */
    public static function init(array $options, LoggerInterface $logger = null);

    /**
     * Возвращает подготовленный массив классов для переменной $CONFIG['classes']
     *
     * @param $folders
     * @return array
     */
    public static function engine_prepare_classes(array $folders): array;

    /**
     * Функция автолоадера классов
     *
     * @param $class
     */
    public static function engine_class_loader(string $class);

    /**
     * Возвращает внутренний путь к контенту
     *
     * @param string $type
     * @param string $creation_date
     * @return string
     */
    public static function getContentPath(string $type = "photos", string $creation_date = ''): string;

    /**
     * Генерирует веб-путь к картинке (упрощенный механизм, для отрисовки фронта)
     *
     * @param string $type
     * @param string $creation_date
     * @param bool $final_slash
     * @return string
     */
    public static function getContentURL(string $type = "photos", $creation_date = '', bool $final_slash = true): string;

    /**
     *
     * @param array $upload_data
     * @param string $where
     * @return string
     */
    public static function parseUploadError(array $upload_data, $where = __METHOD__):string;

    /**
     * Проверяет, обратились ли к текущему скрипту через SSL
     *
     * @return bool
     */
    public static function is_ssl():bool;

    /**
     * Генерируем новое имя файла на основе даты.
     * Формат: ПРЕФИКС + СОЛЬ + СУФФИКС
     *
     * ПРЕФИКС делается на основе текущей (!) даты по переданной маске. Если маска пустая - префикс пуст
     * СОЛЬ - случайная строка [a-z0-9]
     * СУФФИКС - строка, если не пуста - заменяется на "{суффикс}"
     *
     * @param int $length - длина соли
     * @param string $suffix - суффикс имени файла.
     * @param string $prefix_format - формат даты в префиксе (Ymd)
     * @return string
     */
    public static function getRandomFilename(int $length = 20, string $suffix = '', $prefix_format = 'Ymd'):string;

    /**
     * Генерация рэндомных строк заданной длины
     *
     * @param $length
     * @return string
     */
    public static function getRandomString(int $length):string;

    /**
     * Загружает версию движка из version-файла
     *
     * @return array
     */
    public static function getEngineVersion():array;

    /**
     * Функция формирования метрик статистики сайта
     *
     * @param MySQLWrapper $mysql instance
     * @param array $config global site config
     * @return array
     */
    public static function getSiteUsageMetrics(MySQLWrapper $mysql, array $config):array;

    /**
     * Функция логгирования собранных метрик и, возможно, печати их в поток вывода
     *
     * @param LoggerInterface $logger
     * @param array $metrics
     * @param bool $is_print
     * @return bool
     */
    public static function logSiteUsage(LoggerInterface $logger, array $metrics, bool $is_print = false);

    /**
     * Очищает пользовательский ввод от некорректных данных
     *
     * @param $body
     * @param array $bad_values ['+', '-', '~', '(', ')', '*', '"', '>', '<']
     * @return mixed
     */
    public static function sanitizeHTMLData($body, $bad_values = ['+', '-', '~', '(', ')', '*', '"', '>', '<']);

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
    public static function simpleSendEMAIL($to, $from = "", $subject = "", $message = "", $fromname = "");

    /**
     * Применяется только на ДП. Выпилить?
     *
     * @param $input
     * @return string
     */
    public static function unEscapeString($input);

    /**
     * Десериализует данные или возвращает значение по умолчанию
     * в случае их "пустоты"
     *
     * @param $data
     * @param $default_value
     */
    public static function normalizeSerialData(&$data, array $default_value = []);
    
    /**
     *
     * @param array $options
     * @param null $key
     * @param null $default_value
     * @return mixed
     */
    public static function setOption(array $options = [], $key = null, $default_value = null);
}