<?php

// ХЕЛПЕРЫ ДЛЯ picture class

if (!function_exists('getfixedpicture')) {

    /**
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     * @throws Exception
     */
    function getfixedpicture($fn_source, $fn_target, $maxwidth, $maxheight)
    {
        return \SteamBoat\GDWrapper::getFixedPicture($fn_source, $fn_target, $maxwidth, $maxheight);
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
        return \SteamBoat\GDWrapper::resizeImageAspect($fn_source, $fn_target, $maxwidth, $maxheight);
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
        return \SteamBoat\GDWrapper::verticalimage($fn_source, $fn_target, $maxwidth, $maxheight);
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
        return \SteamBoat\GDWrapper::resizePictureAspect($fn_source, $fn_target, $maxwidth, $maxheight);
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




