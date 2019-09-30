<?php


namespace SteamBoat;

interface SBEngineInterface
{
    /**
     * Инициализирует SteamBoat Engine
     *
     * @param array $options
     * @param $logger
     */
    public static function init(array $options, $logger = null);

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
     * Загружает валюты из JSON-файла
     *
     * @return array
     */
    public static function loadCurrencies(): array;

    /**
     *
     * @param array $upload_data
     * @param string $where
     * @return string
     */
    public static function parseUploadError(array $upload_data, $where = __METHOD__):string;
}