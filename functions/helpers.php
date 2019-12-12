<?php
// ХЕЛПЕРЫ, вне неймпейса

use SteamBoat\GDWrapper;

/**
 * Хелперы функций движка.
 *
 * Interface SteamBoatHelpers
 */
interface SteamBoatHelpers
{
    function getfixedpicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;
    function resizeimageaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;
    function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;
    function resizepictureaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;
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
    function resizeimageaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool
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
    function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool
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
    function resizepictureaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool
    {
        return GDWrapper::resizePictureAspect($fn_source, $fn_target, $maxwidth, $maxheight);
    }
}

# -eof-
