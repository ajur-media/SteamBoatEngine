<?php


namespace SteamBoat;


use Monolog\Logger;

interface GDWrapperInterface
{
    /**
     *
     * @param $options
     * @param Logger $logger
     */
    public static function init($options, $logger = null);

    /**
     * вписывает изображение в указанные размеры
     *
     * = resizeimageaspect()
     *
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     */
    public static function resizeImageAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;

    /**
     * Ресайзит картинку по большей из сторон
     *
     * = resizepictureaspect()
     *
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     */
    public static function resizePictureAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool ;

    /**
     *
     * = verticalimage()
     *
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     */
    public static function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool ;

    /**
     * Ресайзит картинку в фиксированные размеры
     *
     * = getfixedpicture()
     *
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth - maximal target width
     * @param $maxheight - maximal target height
     * @return bool
     */
    public static function getFixedPicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;

    /**
     * Добавляет на изображение вотермарк (
     *
     * = addwatermark()
     *
     * @param string $fn_source
     * @param array $params
     * @param int $pos_index
     * @return bool
     */
    public static function addWaterMark(string $fn_source, array $params, int $pos_index):bool;

    /**
     * NEVER USED
     *
     * = rotate()
     *
     * @param $fn_source
     * @param string $dist
     * @return bool
     */
    public static function rotate(string $fn_source, string $dist = ""):bool;

    /**
     * Используется на 47news
     *
     *
     * = rotate2()
     *
     * @param $fn_source
     * @param string $dist
     * @return bool
     */
    public static function rotate2(string $fn_source, string $dist = ""):bool ;
}