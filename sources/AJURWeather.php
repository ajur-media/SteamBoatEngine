<?php

namespace SteamBoat;

use Cmfcmf\OpenWeatherMap\CurrentWeather;
use Monolog\Logger;

use function Arris\array_search_callback as array_search_callback;

/**
 * Class AJURWeather
 *
 * Использовать:
 *  AJURWeather::init( AppLogger::scope('weather') );
 *  AJURWeather::load_weather_local()
 *
 * @package SteamBoat
 */
class AJURWeather implements AJURWeatherInterface, AJURWeatherConstants
{
    /**
     * @var Logger
     */
    private static $logger;

    public static function init($logger)
    {
        self::$logger = $logger;
    }

    public static function load_weather_local($district_id = 0, $source_file = null)
    {
        $current_weather = [];

        try {
            if (is_null($source_file))
                throw new \Exception("Weather file not defined", self::ERROR_SOURCE_FILE_NOT_DEFINED);

            if (!is_readable($source_file))
                throw new \Exception("Weather file `{$source_file}` not found", self::ERROR_SOURCE_FILE_NOT_READABLE);

            $file_content = \file_get_contents($source_file);
            if ($file_content === FALSE)
                throw new \Exception("Error reading weather file `{$source_file}`", self::ERROR_SOURCE_FILE_READING_ERROR);

            $file_content = \json_decode($file_content, true);

            if (($file_content === NULL) || !\is_array($file_content))
                throw new \Exception("Weather data can't be parsed", self::ERROR_SOURCE_FILE_PARSING_ERROR);

            if (!\array_key_exists('data', $file_content))
                throw new \Exception("Weather file does not contain DATA section", self::ERROR_SOURCE_FILE_HAVE_NO_DATA);

            $current_weather = $file_content['data'];

            // Погода загружена. Перемешаем массив.
            \shuffle($current_weather);

            // Район - 0 (все) ?
            if ($district_id === 0) {
                return $current_weather; // возвращаем перемешанный массив с погодой
            }

            // Район не равен нулю, нужно построить массив с погодой для указанного района и ближайших:

            // проверим, есть ли такой идентификатор района вообще в массиве кодов районов.
            // Если нет - кидаем исключение (записываем ошибку), но возвращаем массив со случайной погодой
            if (!array_key_exists($district_id, self::map_intid_to_owmid[ 813 ]))
                throw new \Exception("Given district id ({$district_id}) does not exist in MAP_INTID_TO_OWMID set", self::ERROR_NO_SUCH_DISTRICT_ID);

            /**
             * array_search_callback() аналогичен array_search() , только помогает искать по неодномерному массиву.
             */
            $local_weather = [];

            // первый элемент - погода текущего региона
            $district_owmid = self::map_intid_to_owmid[ 813 ][ $district_id ];

            $local_weather[] = array_search_callback($current_weather, function ($item) use ($district_owmid){
                return ($item['id'] == $district_owmid);
            });

            // ближайшие регионы
            foreach (self::lo_adjacency_lists[ $district_id ] as $adjacency_district_id ) {

                $adjacency_district_owmid = self::map_intid_to_owmid[ 813 ][ $adjacency_district_id ];

                $local_weather[] = array_search_callback($current_weather, function ($item) use ($adjacency_district_owmid){
                    return ($item['id'] == $adjacency_district_owmid);
                });
            }

            return $local_weather;

        } catch (\Exception $e) {
            if (self::$logger instanceof Logger) {
                self::$logger->error('[ERROR] Load Weather ',
                    [
                        array_search($e->getCode(), (new \ReflectionClass(__CLASS__))->getConstants()),
                        $e->getMessage()
                    ]);
            }
        }

        return $current_weather;

    } // load_weather_local

    public static function makeWeatherInfo($id, CurrentWeather $weather):array
    {
        $info = [
            'id'            =>  $id,
            'name'          =>  self::outer_regions[ $id ]['geoname_ru'],
            'temperature'   =>  $weather->temperature->now->getValue()      ?? 0,
            'humidity'      =>  $weather->humidity->getFormatted()          ?? '0 %',     // форматированное, с %
            'pressure_hpa'  =>  $weather->pressure->getValue()              ?? 0,         // в гектопаскалях, сырое значение
            'pressure_mm'   =>  round(($weather->pressure->getValue()   ?? 0) * 0.75006375541921, 0),
            'wind_speed'    =>  $weather->wind->speed->getValue()           ?? 0,      // м/с, сырое
            'wind_dir_raw'  =>  $weather->wind->direction->getValue()       ?? 0,    //@todo: ЕСЛИ ПРИШЛО NULL то будет ошибка
            'wind_dir'      =>  $weather->wind->direction->getUnit()        ?? '',   // направление, аббревиатурой (NULL не ломает)
            'clouds_value'  =>  $weather->clouds->getValue()                ?? 0,            // облачность (% значение)
            'clouds_text'   =>  $weather->clouds->getDescription()          ?? '',      // облачность, текстом
            'precipitation' =>  $weather->precipitation->getValue()         ?? 0,    // осадки, сырое значение
            'weather_icon'  =>  $weather->weather->icon                     ?? '',                 // погодная иконка, название
            'weather_icon_url'  =>  $weather->weather->getIconUrl()         ?? '',     // погода, текстом
            't'         =>  round(($weather->temperature->now->getValue()   ?? 0), 0),
            's'         =>  array_key_exists($weather->weather->icon, self::icons_conversion)
                ? self::icons_conversion[ $weather->weather->icon ]
                : '44d',
        ];
        return $info;
    }

    public static function makeWeatherInfoJSON(int $id, CurrentWeather $region_weather):array {
        $info = [
            'id'            =>  $id,
            'name'          =>  self::outer_regions[ $id ]['geoname_ru'],
            'temperature'   =>  round($region_weather['main']['temp'], 0),
            'humidity'      =>  ($region_weather['main']['humidity'] ?? '0')  . '%',     // форматированное, с %
            'pressure_hpa'  =>  round($region_weather['main']['pressure_hpa'] ?? 0, 0),         // в гектопаскалях, сырое значение
            'wind_speed'    =>  round($region_weather['wind']['speed'] ?? 0, 0),
            'wind_dir_raw'  =>  $region_weather['wind']['deg'] ?? 0,                    // направление, градусы

            'clouds_value'  =>  $region_weather->clouds->getValue()                ?? 0,            // облачность (% значение)
            'clouds_text'   =>  $region_weather->clouds->getDescription()          ?? '',      // облачность, текстом
            'precipitation' =>  $region_weather->precipitation->getValue()         ?? 0,    // осадки, сырое значение
            'weather_icon'  =>  $region_weather->weather->icon                     ?? '',                 // погодная иконка, название
            'weather_icon_url'  =>  $region_weather->weather->getIconUrl()         ?? '',     // погода, текстом
            't'         =>  round(($region_weather->temperature->now->getValue()   ?? 0), 0),
            's'         =>  array_key_exists($region_weather->weather->icon, self::icons_conversion)
                ? self::icons_conversion[ $region_weather->weather->icon ]
                : '44d',
        ];

        $info['pressure_mm'] = round(($region_weather['main']['pressure_hpa'] ?? 0) * 0.75006375541921, 0);

        return $info;
    }


}