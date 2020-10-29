<?php

namespace SteamBoat;

use Psr\Log\LoggerInterface;

interface GDWrapperInterface
{
    /**
     *
     * @param $options
     * @param LoggerInterface $logger
     */
    public static function init($options = [], LoggerInterface $logger = null);

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
    public static function resizeImageAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool;

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
    public static function resizePictureAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool ;

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
    public static function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool ;
    
    /**
     * Ресайзит картинку в фиксированные размеры
     *
     * = getfixedpicture()
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth - maximal target width
     * @param int $maxheight - maximal target height
     * @param int|null $image_quality - качество картинки (null) означает взять из настроек класса
     * @return bool
     */
    public static function getFixedPicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, int $image_quality = null):bool;

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