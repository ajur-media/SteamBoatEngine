<?php

// ХЕЛПЕРЫ

use SteamBoat\GDWrapper;

interface SteamBoatHelpers {

    function getfixedpicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;


}

if (!function_exists('getfixedpicture')) {

    /**
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     * @throws Exception
     */
    function getfixedpicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool
    {
        return GDWrapper::getFixedPicture($fn_source, $fn_target, $maxwidth, $maxheight);
    }
}

if (!function_exists('resizeimageaspect')) {

    /**
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     */
    function resizeimageaspect($fn_source, $fn_target, $maxwidth, $maxheight)
    {
        return GDWrapper::resizeImageAspect($fn_source, $fn_target, $maxwidth, $maxheight);
    }
}

if (!function_exists('verticalimage')) {

    /**
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     * @throws Exception
     */
    function verticalimage($fn_source, $fn_target, $maxwidth, $maxheight)
    {
        return GDWrapper::verticalimage($fn_source, $fn_target, $maxwidth, $maxheight);
    }
}

if (!function_exists('resizepictureaspect')) {

    /**
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     * @throws Exception
     */
    function resizepictureaspect($fn_source, $fn_target, $maxwidth, $maxheight)
    {
        return GDWrapper::resizePictureAspect($fn_source, $fn_target, $maxwidth, $maxheight);
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
    function normalizeSerialData(&$data, $default_value = [])
    {
        $data = empty($data) ? $default_value : @unserialize($data);
    }
}

if (!function_exists('array_map_to_integer')) {
    /**
     * Хелпер преобразования всех элементов массива к типу integer
     *
     * @param array $input
     * @return array
     */
    function array_map_to_integer(array $input): array
    {
        return array_map(function ($i) {
            return intval($i);
        }, $input);
    }
}


