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
    function getResourcePath($type = "photos", $cdate = null): string;
    function getimagepath($type = "photos", $cdate = null):string;
    function pluralForm($number, $forms, string $glue = '|'):string;

    function getfixedpicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool;
    function resizeimageaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool;
    function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool;
    function resizepictureaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool;

    function toRange($value, $min, $max);
}

if (!function_exists('getResourcePath')) {
    /**
     * Возвращает путь до ресурса в symlinked-хранилище
     *
     * @param string $type
     * @param null $cdate
     * @return string
     */
    function getResourcePath($type = "photos", $cdate = null): string
    {
        $cdate = is_null($cdate) ? time() : strtotime($cdate);
        
        $path
            = getenv('PATH.INSTALL')
            . 'www/i/'
            . $type
            . DIRECTORY_SEPARATOR
            . date("Y/m", $cdate);
        
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        
        return $path;
    }
}

if (!function_exists('getimagepath')) {

    /**
     * Возвращает путь до ресурса
     *
     * @param string $type
     * @param null $cdate
     * @return string
     */
    function getimagepath($type = "photos", $cdate = null):string
    {
        $cdate = is_null($cdate) ? time() : strtotime($cdate);

        $path
            = getenv('PATH.INSTALL')
            . "/www/i/"
            . $type
            . DIRECTORY_SEPARATOR
            . date("Y/m", $cdate)
            . DIRECTORY_SEPARATOR;

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }
}

if (!function_exists('pluralForm')) {
    /**
     *
     * @param $number
     * @param array $forms (array or string with glues, x|y|z or [x,y,z]
     * @param string $glue
     * @return string
     */
    function pluralForm($number, $forms, string $glue = '|'):string
    {
        if (is_string($forms)) {
            $forms = explode($forms, $glue);
        } elseif (!is_array($forms)) {
            return '';
        }

        if (count($forms) != 3) return '';

        return
            ($number % 10 == 1 && $number % 100 != 11)
                ? $forms[0]
                : (
            ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20))
                ? $forms[1]
                : $forms[2]
            );
    }
}

if (!function_exists('cropimage')) {
    
    /**
     * CropImage helper
     *
     * @param string $fn_source
     * @param string $fn_target
     * @param array $xy_source
     * @param array $wh_dest
     * @param array $wh_source
     * @param null $quality
     * @return bool
     */
    function cropImage(string $fn_source, string $fn_target, array $xy_source, array $wh_dest, array $wh_source, $quality = null): bool
    {
        return GDWrapper::cropImage($fn_source, $fn_target, $xy_source, $wh_dest, $wh_source, $quality);
    }
}

if (!function_exists('getfixedpicture')) {
    
    /**
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return bool
     */
    function getfixedpicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool
    {
        return GDWrapper::getFixedPicture($fn_source, $fn_target, $maxwidth, $maxheight, $image_quality);
    }
}

if (!function_exists('resizeimageaspect')) {
    
    /**
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return bool
     */
    function resizeimageaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool
    {
        return GDWrapper::resizeImageAspect($fn_source, $fn_target, $maxwidth, $maxheight, $image_quality);
    }
}

if (!function_exists('verticalimage')) {
    
    /**
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return bool
     */
    function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool
    {
        return GDWrapper::verticalimage($fn_source, $fn_target, $maxwidth, $maxheight, $image_quality);
    }
}

if (!function_exists('resizepictureaspect')) {
    
    /**
     * @param string $fn_source
     * @param string $fn_target
     * @param int $maxwidth
     * @param int $maxheight
     * @param null $image_quality
     * @return bool
     */
    function resizepictureaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):bool
    {
        return GDWrapper::resizePictureAspect($fn_source, $fn_target, $maxwidth, $maxheight, $image_quality);
    }
}

if (!function_exists('toRange')) {
    /**
     *
     * @param $value
     * @param $min
     * @param $max
     * @return mixed
     */
    function toRange($value, $min, $max)
    {
        return max($min, min($value, $max));
    }
}

/**
 * Required by 47news
 */
if (!function_exists('smarty_modifier_html_substr')) {
    
    /**
     * https://stackoverflow.com/a/49094841
     * @param $string
     * @param $length
     * @param string $addstring
     * @return bool|string
     *
     * Same problem: https://stackoverflow.com/questions/1193500/truncate-text-containing-html-ignoring-tags
     */
    function smarty_modifier_html_substr($string, $length, $addstring = "")
    {
        
        //some nice italics for the add-string
        if (!empty($addstring)) $addstring = "<i> " . $addstring . "</i>";
        
        if (strlen($string) > $length) {
            if (!empty($string) && $length > 0) {
                $isText = true;
                $ret = "";
                $i = 0;
                
                $lastSpacePosition = -1;
                
                $tagsArray = array();
                $currentTag = "";
                
                $addstringAdded = false;
                
                $noTagLength = strlen(strip_tags($string));
                
                // Parser loop
                for ($j = 0; $j < strlen($string); $j++) {
                    
                    $currentChar = substr($string, $j, 1);
                    $ret .= $currentChar;
                    
                    // Lesser than event
                    if ($currentChar == "<") $isText = false;
                    
                    // Character handler
                    if ($isText) {
                        
                        // Memorize last space position
                        if ($currentChar == " ") {
                            $lastSpacePosition = $j;
                        } else {
                            $lastChar = $currentChar;
                        }
                        
                        $i++;
                    } else {
                        $currentTag .= $currentChar;
                    }
                    
                    // Greater than event
                    if ($currentChar == ">") {
                        $isText = true;
                        
                        // Opening tag handler
                        if ((strpos($currentTag, "<") !== FALSE) &&
                            (strpos($currentTag, "/>") === FALSE) &&
                            (strpos($currentTag, "</") === FALSE)) {
                            
                            // Tag has attribute(s)
                            if (strpos($currentTag, " ") !== FALSE) {
                                $currentTag = substr($currentTag, 1, strpos($currentTag, " ") - 1);
                            } else {
                                // Tag doesn't have attribute(s)
                                $currentTag = substr($currentTag, 1, -1);
                            }
                            
                            array_push($tagsArray, $currentTag);
                            
                        } else if (strpos($currentTag, "</") !== FALSE) {
                            array_pop($tagsArray);
                        }
                        
                        $currentTag = "";
                    }
                    
                    if ($i >= $length) {
                        break;
                    }
                }
                
                // Cut HTML string at last space position
                if ($length < $noTagLength) {
                    if ($lastSpacePosition != -1) {
                        $ret = substr($string, 0, $lastSpacePosition);
                    } else {
                        $ret = substr($string, $j);
                    }
                }
                
                // Close broken XHTML elements
                while (sizeof($tagsArray) != 0) {
                    $aTag = array_pop($tagsArray);
                    // if a <p> or <li> tag needs to be closed, put the add-string in first
                    if (($aTag == "p" || $aTag == "li") && strlen($string) > $length) {
                        $ret .= $addstring;
                        $addstringAdded = true;
                    }
                    $ret .= "</" . $aTag . ">\n";
                }
                
            } else {
                $ret = "";
            }
            
            // if we have not added the add-string already
            if (strlen($string) > $length && $addstringAdded == false) {
                return ($ret . $addstring);
            } else {
                return ($ret);
            }
        } else {
            return ($string);
        }
    }
    
}

/**
 * Doctor Piter
 */
if (!function_exists('convert_UTF16BE_to_UTF8')){
    function convert_UTF16BE_to_UTF8($t)
    {
        //return preg_replace( '#%u([0-9A-F]{1,4})#ie', "'& #'.hexdec('\\1').';'", $t );
        // return preg_replace('#%u([0-9A-F]{4})#se', 'iconv("UTF-16BE","UTF-8",pack("H4","$1"))', $t);
        return preg_replace_callback('#%u([0-9A-F]{4})#s', function (){
            iconv("UTF-16BE","UTF-8", pack("H4","$1"));
        }, $t);
    }
}

if (!function_exists('rewrite_hrefs_to_blank')) {
    
    /**
     *
     * @param $text
     * @return string|string[]|null
     */
    function rewrite_hrefs_to_blank(string $text):string
    {
        return preg_replace_callback("/<a([^>]+)>(.*?)<\/a>/i", function ($matches) {
            $matches[1] = trim($matches[1]);
            $arr = [];
            
            $matches[1] = preg_replace("/([\"']{0,})\s([a-zA-Z]+\=(\"|'))/i", "$1-!break!-$2", $matches[1]);
            $matches[1] = explode("-!break!-", $matches[1]);
            
            // предполагаем, что по-умолчанию у всех ссылок нужно ставить target=_blank
            $blank = true;
            foreach ($matches[1] as $v) {
                $r = explode("=", $v, 2);
                $r[1] = trim($r[1], "'");
                $r[1] = trim($r[1], '"');
                $arr[$r[0]] = $r[1];
                if ($r[0] == "href") {
                    // условия, исключающие установку target=_blank
                    if (!preg_match("/([a-zA-Z]+)\:\/\/(.*)/i", $r[1])) {
                        // ссылка не начинается с какого-либо протокола, соот-но она внутренняя и новое окно не нужно
                        $blank = false;
                    } else {
                        // ссылка внешняя, и нам надо понять, не ведет ли она в наш же домен
                        if (stristr($r[1], getenv('DOMAIN.FQDN'))) $blank = false;
                    }
                }
            }
            // если уже есть в списке target, то ничего не делаем
            foreach ($arr as $k => $v) {
                if ($k == "blank") $blank = false;
            }
            if ($blank) $arr['target'] = "_blank";
            $prms = array();
            foreach ($arr as $k => $v) {
                $prms[] = "{$k}=\"{$v}\"";
            }
            $params_as_string = implode(" ", $prms);
            return "<a {$params_as_string}>{$matches[2]}</a>";
        }, $text);
        
    }
}


# -eof-

