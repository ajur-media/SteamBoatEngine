<?php

namespace SteamBoat;

use Cmfcmf\OpenWeatherMap\CurrentWeather;

interface AJURWeatherInterface {

    /**
     *
     * @param $logger
     */
    public static function init($logger);

    /**
     *
     * @param $district_id
     * @param $source_file
     * @return array
     * @throws \Exception
     */
    public static function load_weather_local($district_id = 0, $source_file = null);

    /**
     * @param $id
     * @param CurrentWeather $weather
     * @return array
     */
    public static function makeWeatherInfo($id, CurrentWeather $weather):array;

    /**
     * @param int $id
     * @param CurrentWeather $region_weather
     * @return array
     */
    public static function makeWeatherInfoJSON(int $id, CurrentWeather $region_weather):array;
}