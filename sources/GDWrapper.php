<?php
/**
 * Created with PhpStorm.
 * User: wombat
 * Date: 15.05.2019
 * Time: 14:36
 */

namespace SteamBoat;

use Arris\AppLogger;

interface GDWrapperInterface {

    public static function init();

    public static function resizeImageAspect($fn_source, $fn_target, $maxwidth, $maxheight);
    public static function resizePictureAspect($fn_source, $fn_target, $maxwidth, $maxheight);
    public static function verticalimage($fn_source, $fn_target, $maxwidth, $maxheight);

    public static function getFixedPicture($fn_source, $fn_target, $maxwidth, $maxheight);
    public static function addWaterMark($fn_source, $params, $pos_index);

    public static function rotate($fn_source, $dist = "");
    public static function rotate2($fn_source, $dist = "");
}

class GDWrapper
{
    const VERSION = "3.0";

    public static $default_jpeg_quality = 100;

    public static function init()
    {
        self::$default_jpeg_quality = getenv('ADMIN_JPEG_COMPRESSION_QUALITY') ?: 100;

        if (getenv('DEBUG_LOG_CONSTRUCTOR_CALL')) AppLogger::scope('main')->info("Created static " . __CLASS__ . " from " . __FILE__, [microtime(true)]);
    }

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
    public static function resizeImageAspect($fn_source, $fn_target, $maxwidth, $maxheight)
    {
        if (!is_readable($fn_source)) {
            AppLogger::scope('main')->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);

        list($image_source, $extension) = self::createImageFromFile($fn_source, $type);

        if ($image_source) {
            $new_image_sizes = self::getNewSizes($width, $height, $maxwidth, $maxheight);

            $newwidth = $new_image_sizes[0];
            $newheight = $new_image_sizes[1];

            // Resize
            $image_destination = imagecreatetruecolor($newwidth, $newheight);
            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, true);
                imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 0, 0, 0, 127));
            }

            imagecopyresampled($image_destination, $image_source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, false);
                imagesavealpha($image_destination, true);
            }

            self::storeImageToFile($fn_target, $image_destination, $extension);

            imagedestroy($image_destination);
            imagedestroy($image_source);
            return true;
        } else {
            echo "not image {$fn_source}";
            return false;
        }

    }


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
     * @throws \Exception
     */
    public static function resizePictureAspect($fn_source, $fn_target, $maxwidth, $maxheight)
    {
        if (!is_readable($fn_source)) {
            AppLogger::scope('main')->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);

        list($image_source, $extension) = self::createImageFromFile($fn_source, $type);

        if ($image_source) {

            // horizontal image
            if ($width > $maxwidth) {
                $newwidth = $maxwidth;
                $newheight = ((float)$maxwidth / (float)$width) * $height;
            } else {
                $newwidth = $width;
                $newheight = $height;
            }

            // Resize
            $image_destination = imagecreatetruecolor($newwidth, $newheight);

            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, true);
                imagealphablending($image_source, true);
                imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 0, 0, 0, 127));
            }
            imagecopyresampled($image_destination, $image_source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, false);
                imagecolortransparent($image_destination, imagecolorat($image_destination, 0, 0));
                imagesavealpha($image_destination, true);
            }

            self::storeImageToFile($fn_target, $image_destination, $extension);

            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * = verticalimage()
     *
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     * @throws \Exception
     */
    public static function verticalimage($fn_source, $fn_target, $maxwidth, $maxheight)
    {
        if (!is_readable($fn_source)) {
            AppLogger::scope('main')->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source, $type);

        if ($image_source) {
            $newheight = $maxheight;
            $newwidth = ((float)$maxheight / (float)$height) * $width;

            // Resize
            $image_destination = imagecreatetruecolor($newwidth, $newheight);

            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, true);
                imagealphablending($image_source, true);
                imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 0, 0, 0, 127));
            }
            imagecopyresampled($image_destination, $image_source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, false);
                imagecolortransparent($image_destination, imagecolorat($image_destination, 0, 0));
                imagesavealpha($image_destination, true);
            }

            self::storeImageToFile($fn_target, $image_destination, $extension);

            imagedestroy($image_destination);
            imagedestroy($image_source);
            return true;
        } else {
            return false;
        }
    }


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
     * @throws \Exception
     */
    public static function getFixedPicture($fn_source, $fn_target, $maxwidth, $maxheight)
    {
        if (!is_readable($fn_source)) {
            AppLogger::scope('main')->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source, $type);

        if ($image_source) {
            $minx = 0;
            $miny = 0;

            if ($width > $height) {
                // горизонтальная
                $k = $height / $maxheight;
                $miny = $maxheight;
                $minx = $width / $k;
                if ($minx < $maxwidth) {
                    $minx = $maxwidth;
                    $miny = $maxwidth * $height / $width;
                }
            } else {
                // вертикальная
                $k = $width / $maxwidth;
                $minx = $maxwidth;
                $miny = $height / $k;
                if ($miny < $maxheight) {
                    $minx = $maxheight * $width / $height;
                    $miny = $maxheight;
                }
            }

            // Resize
            $image_destination = imagecreatetruecolor($minx, $miny);
            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, true);
                imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 255, 255, 255, 127));
            }
            imagecopyresampled($image_destination, $image_source, 0, 0, 0, 0, $minx, $miny, $width, $height);
            if ($extension == "gif" or $extension == "png") {
                imagealphablending($image_destination, false);
                imagesavealpha($image_destination, true);
            }

            $im_res = $image_destination;

            $image_destination = imagecreatetruecolor($maxwidth, $maxheight);

            // вырезаем из получившегося куска нужный размер

            if ($minx == $maxwidth) {
                // по горизонтали ок, центруем вертикаль и режем
                $start = ceil(($miny - $maxheight) / 2);
                if ($extension == "gif" or $extension == "png") {
                    imagealphablending($image_destination, true);
                    imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 255, 255, 255, 127));
                }
                imagecopy($image_destination, $im_res, 0, 0, 0, $start, $maxwidth, $maxheight);
                if ($extension == "gif" or $extension == "png") {
                    imagealphablending($image_destination, false);
                    imagesavealpha($image_destination, true);
                }
            }

            if ($miny == $maxheight) {
                $start = ceil(($minx - $maxwidth) / 2);
                if ($extension == "gif" or $extension == "png") {
                    imagealphablending($image_destination, true);
                    imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 255, 255, 255, 127));
                }
                imagecopy($image_destination, $im_res, 0, 0, $start, 0, $maxwidth, $maxheight);
                if ($extension == "gif" or $extension == "png") {
                    imagealphablending($image_destination, false);
                    imagesavealpha($image_destination, true);
                }
            }

            self::storeImageToFile($fn_target, $image_destination, $extension);

            imagedestroy($image_destination);
            imagedestroy($image_source);
            imagedestroy($im_res);
            return true;
        } else {
            return false;
        }
    }

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
    public static function addWaterMark($fn_source, $params, $pos_index)
    {
        $watermark = $params['watermark'];
        $margin = $params['margin'];
        $positions = array(
            1 => "left-top",
            2 => "right-top",
            3 => "right-bottom",
            4 => "left-bottom",
        );
        if (!in_array($pos_index, array_keys($positions))) return false;

        $watermark = imagecreatefrompng($watermark);

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source, $type);

        if ($image_source) {
            $image_width = imagesx($image_source);
            $image_height = imagesy($image_source);
            $watermark_width = imagesx($watermark);
            $watermark_height = imagesy($watermark);

            switch ($pos_index) {
                case 1:         // "left-top"
                    {
                        $ns_x = $margin;
                        $ns_y = $margin;
                        break;
                    }
                case 2:         // "right-top"
                    {
                        $ns_x = $image_width - $margin - $watermark_width;
                        $ns_y = $margin;
                        break;
                    }
                case 3:         // "right-bottom"
                    {
                        $ns_x = $image_width - $margin - $watermark_width;
                        $ns_y = $image_height - $margin - $watermark_height;
                        break;
                    }
                case 4:         // "left-bottom"
                    {
                        $ns_x = $margin;
                        $ns_y = $image_height - $margin - $watermark_height;
                        break;
                    }
            }

            imagealphablending($image_source, TRUE);
            imagealphablending($watermark, TRUE);
            imagecopy($image_source, $watermark, $ns_x, $ns_y, 0, 0, $watermark_width, $watermark_height);
            imagedestroy($watermark);

            self::storeImageToFile($fn_source, $image_source, $extension);

            imagedestroy($image_source);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Используется на 47news
     *
     *
     * = rotate2()
     *
     * @param $fn_source
     * @param string $dist
     * @return bool
     * @throws \Exception
     */
    public static function rotate2($fn_source, $dist = "")
    {
        if (!is_readable($fn_source)) {
            AppLogger::scope('main')->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source, $type);

        if ($image_source) {
            $degrees = 0;
            if ($dist == "left") {
                $degrees = 90;
            }
            if ($dist == "right") {
                $degrees = 270;
            }
            $image_destination = imagerotate($image_source, $degrees, 0);

            self::storeImageToFile($fn_source, $image_destination, $extension);

            return true;
        } else {
            return false;
        }
    }

    /**
     * NEVER USED
     *
     * = rotate()
     *
     * @param $fn_source
     * @param string $dist
     * @return bool
     * @throws \Exception
     */
    public static function rotate($fn_source, $dist = "")
    {
        if (!is_readable($fn_source)) {
            AppLogger::scope('main')->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }

        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source, $type);

        if ($image_source) {
            $degrees = 0;
            if ($dist == "left") {
                $degrees = 270;
            }
            if ($dist == "right") {
                $degrees = 90;
            }
            $image_destination = self::rotateimage($image_source, $degrees);

            self::storeImageToFile($fn_source, $image_destination, $extension);

            return true;
        } else {
            return false;
        }
    }



    /* ====================================================================================================== */


    private static function getNewSizes($width, $height, $maxwidth, $maxheight)
    {

        if ($width > $height) {
            // горизонтальная
            if ($maxwidth < $width) {
                $newwidth = $maxwidth;
                $newheight = ceil($height * $maxwidth / $width);
            } else {
                $newheight = $height;
                $newwidth = $width;
            }
        } else {
            // вертикальная
            if ($maxheight < $height) {
                $newheight = $maxheight;
                $newwidth = ceil($width * $maxheight / $height);
            } else {
                $newheight = $height;
                $newwidth = $width;
            }
        }
        return array($newwidth, $newheight);
    }

    private static function createImageFromFile($fname, $type)
    {
        if ($type == IMAGETYPE_BMP) {
            return [ null, null ];
        } else if ($type == IMAGETYPE_PSD) {
            $ext = 'psd';
            $im = imagecreatefrompsd($fname);
        } else if ($type == IMAGETYPE_SWF) {
            $ext = 'swf';
            $im = imagecreatefromswf($fname);
        } else if ($type == IMAGETYPE_PNG) {
            $ext = 'png';
            $im = imagecreatefrompng($fname);
        } else if ($type == IMAGETYPE_JPEG) {
            $ext = 'jpg';
            $im = imagecreatefromjpeg($fname);
        } else if ($type == IMAGETYPE_GIF) {
            $ext = 'gif';
            $im = imagecreatefromgif($fname);
        }

        return [ $im, $ext ];
    }

    private static function rotateimage($img, $rotation)
    {
        $width = imagesx($img);
        $height = imagesy($img);
        switch ($rotation) {
            case 90:
                $newimg = @imagecreatetruecolor($height, $width);
                break;
            case 180:
                $newimg = @imagecreatetruecolor($width, $height);
                break;
            case 270:
                $newimg = @imagecreatetruecolor($height, $width);
                break;
            case 0:
                return $img;
                break;
            case 360:
                return $img;
                break;
        }
        if ($newimg) {
            for ($i = 0; $i < $width; $i++) {
                for ($j = 0; $j < $height; $j++) {
                    $reference = imagecolorat($img, $i, $j);
                    switch ($rotation) {
                        case 90:
                            if (!@imagesetpixel($newimg, ($height - 1) - $j, $i, $reference)) {
                                return false;
                            }
                            break;
                        case 180:
                            if (!@imagesetpixel($newimg, $width - $i, ($height - 1) - $j, $reference)) {
                                return false;
                            }
                            break;
                        case 270:
                            if (!@imagesetpixel($newimg, $j, $width - $i - 1, $reference)) {
                                return false;
                            }
                            break;
                    }
                }
            }
            return $newimg;
        }
        return false;
    }

    private static function storeImageToFile($fn_target, $image_destination, $extension)
    {
        $result = false;

        // JPG/PNG/GIF/JPG
        if ($extension == "jpg") {
            $result = imagejpeg($image_destination, $fn_target, self::$default_jpeg_quality);
        } elseif ($extension == "png") {
            $result = imagepng($image_destination, $fn_target);
        } elseif ($extension == "gif") {
            $result = imagegif($image_destination, $fn_target);
        } else {
            $result = imagejpeg($image_destination, $fn_target, self::$default_jpeg_quality);
        }

        return $result;
    }

}