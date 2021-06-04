<?php
/**
 * Created with PhpStorm.
 * User: wombat
 * Date: 15.05.2019
 * Time: 14:36
 */

namespace SteamBoat;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class GDWrapper
 * @package SteamBoat
 *
 *  * Использует уровни логгирования:
 * - error - ошибка: как правило, не найден файл
 *
 */
class GDWrapper implements GDWrapperInterface
{
    const VERSION = "4.1";

    /**
     * @var int
     */
    public static $default_jpeg_quality = 100;

    public static $default_webp_quality = 80;

    /**
     * @var LoggerInterface $logger
     */
    public static $logger = null;
    
    /**
     * @param array $options
     * - JPEG_COMPRESSION_QUALITY       env: STORAGE.JPEG_COMPRESSION_QUALITY       default: 100
     * - WEBP_COMPRESSION_QUALITY       env: STORAGE.WEBP_COMPRESSION_QUALITY       default: 80
     *
     * @param LoggerInterface $logger
     */
    public static function init($options = [], LoggerInterface $logger = null)
    {
        self::$default_jpeg_quality = intval($options['JPEG_COMPRESSION_QUALITY']) ?? 100;
        self::$default_webp_quality = intval($options['WEBP_COMPRESSION_QUALITY']) ?? 80;

        self::$default_jpeg_quality
            = is_int(self::$default_jpeg_quality)
            ? toRange(self::$default_jpeg_quality, 0, 100)
            : 100;

        self::$default_webp_quality
            = is_int(self::$default_webp_quality)
            ? toRange(self::$default_webp_quality, 0, 100)
            : 80;
    
        self::$logger
            = $logger instanceof LoggerInterface
            ? $logger
            : new NullLogger();
    
    }
    
    public static function cropImage(string $fn_source, string $fn_target, array $xy_source, array $wh_dest, array $wh_source, $quality = null): bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
            return false;
        }
        
        list($width, $height, $type) = getimagesize($fn_source);
        list($image_source, $extension) = self::createImageFromFile($fn_source, $type);
        
        if ($image_source) {
            $image_destination = imagecreatetruecolor($wh_dest[0], $wh_dest[1]);
    
            imagecopyresampled(
                $image_destination,
                $image_source,
                0, 0,
                $xy_source[0], $xy_source[1],
                $wh_dest[0], $wh_dest[1],
                $wh_source[0], $wh_source[1]);
    
            self::storeImageToFile($fn_target, $image_destination, $extension, $quality);
    
            imagedestroy($image_destination);
            imagedestroy($image_source);
            return true;
        }
    
        self::$logger->error('Not image: ', [ $fn_source ]);
        echo "not image {$fn_source}";
        return false;
    }

    public static function resizeImageAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
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
            if ($extension == "gif" || $extension == "png") {
                imagealphablending($image_destination, true);
                imagefill($image_destination, 0, 0, imagecolorallocatealpha($image_destination, 0, 0, 0, 127));
            }

            imagecopyresampled($image_destination, $image_source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

            if ($extension == "gif" || $extension == "png") {
                imagealphablending($image_destination, false);
                imagesavealpha($image_destination, true);
            }

            self::storeImageToFile($fn_target, $image_destination, $extension, $image_quality);

            imagedestroy($image_destination);
            imagedestroy($image_source);
            return true;
        }
    
        self::$logger->error('Not image: ', [ $fn_source ]);
        echo "not image {$fn_source}";
        return false;
    }

    /**
     * Создает изображение из файла
     *
     * @param $fname
     * @param $type
     * @return array
     */
    private static function createImageFromFile($fname, $type)
    {
        if ($type == IMAGETYPE_BMP) {
            $ext = 'bmp';
            $im = imagecreatefrombmp($fname);
        } else if ($type == IMAGETYPE_PNG) {
            $ext = 'png';
            $im = imagecreatefrompng($fname);
        } else if ($type == IMAGETYPE_JPEG) {
            $ext = 'jpg';
            $im = imagecreatefromjpeg($fname);
        } else if ($type == IMAGETYPE_GIF) {
            $ext = 'gif';
            $im = imagecreatefromgif($fname);
        } else if ($type == IMAGETYPE_WEBP) {
            $ext = 'webp';
            $im = imagecreatefromwebp($fname);
        } else {
            $ext = '';
            $im = false;
        }

        return [$im, $ext];
    }

    /**
     * @param $width
     * @param $height
     * @param $maxwidth
     * @param $maxheight
     * @return array
     */
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
    
    /**
     * @param $fn_target
     * @param $image_destination
     * @param $extension
     * @param null $image_quality
     * @return bool
     */
    private static function storeImageToFile($fn_target, $image_destination, $extension, $image_quality = null)
    {
        // JPG/PNG/GIF/JPG
        if ($extension === "jpg") {
            $quality = is_null($image_quality) ? self::$default_jpeg_quality : $image_quality;
            $result = imagejpeg($image_destination, $fn_target, $quality);
        } elseif ($extension === "png") {
            $result = imagepng($image_destination, $fn_target);
        } elseif ($extension === "gif") {
            $result = imagegif($image_destination, $fn_target);
        } elseif ($extension === 'webp') {
            $quality = is_null($image_quality) ? self::$default_webp_quality : $image_quality;
            $result = imagewebp($image_destination, $fn_target, $quality);
        } else {
            $quality = is_null($image_quality) ? self::$default_jpeg_quality : $image_quality;
            $result = imagejpeg($image_destination, $fn_target, $quality);
        }

        return $result;
    }

    public static function resizePictureAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
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

            self::storeImageToFile($fn_target, $image_destination, $extension, $image_quality);

            return true;
        }
    
        return false;
    }

    public static function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
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

            self::storeImageToFile($fn_target, $image_destination, $extension, $image_quality);

            imagedestroy($image_destination);
            imagedestroy($image_source);
            return true;
        }
    
        return false;
    }

    public static function getFixedPicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, int $image_quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
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

            self::storeImageToFile($fn_target, $image_destination, $extension, $image_quality);

            imagedestroy($image_destination);
            imagedestroy($image_source);
            imagedestroy($im_res);
            return true;
        }
    
        return false;
    }

    public static function addWaterMark(string $fn_source, array $params, int $pos_index, $quality = null):bool
    {
        $watermark = $params['watermark'];
        $margin = $params['margin'];
        $positions = array(
            1 => "left-top",
            2 => "right-top",
            3 => "right-bottom",
            4 => "left-bottom",
        );
        if (!array_key_exists( $pos_index, $positions )) {
            return false;
        }

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

            self::storeImageToFile($fn_source, $image_source, $extension, $quality);

            imagedestroy($image_source);
            return true;
        }
    
        return false;
    }

    public static function rotate2(string $fn_source, string $dist = "", $quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
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

            self::storeImageToFile($fn_source, $image_destination, $extension, $quality);

            return true;
        }
    
        return false;
    }

    public static function rotate(string $fn_source, string $dist = "", $quality = null):bool
    {
        if (!is_readable($fn_source)) {
            self::$logger->error("Static method " . __METHOD__ . " wants missing file", [$fn_source]);
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

            self::storeImageToFile($fn_source, $image_destination, $extension, $quality);

            return true;
        }
    
        return false;
    }

    /**
     * @param $img
     * @param $rotation
     * @return bool|false|resource
     */
    private static function rotateimage($img, $rotation)
    {
        $width = imagesx($img);
        $height = imagesy($img);
        if ($rotation == 0 || $rotation == 360) {
            return $img;
        }

        $newimg = @imagecreatetruecolor($height, $width);

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

}

# -eof-
