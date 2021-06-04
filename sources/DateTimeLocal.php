<?php
/**
 * Created by PhpStorm.
 * User: wombat
 * Date: 04.03.19
 * Time: 15:50
 */

namespace SteamBoat;

/**
 * Class DateTimeLocal
 * @package SteamBoat
 *
 * Логгирование: не используется
 */
class DateTimeLocal implements DateTimeLocalInterface
{
    const VERSION = '1.13';

    public static function convertDateRu(string $datetime, bool $is_show_time = false, string $year_suffix = 'г.'):string
    {
        return self::convertDate($datetime, $is_show_time, $year_suffix);
    }

    public static function convertDate(string $datetime, bool $is_show_time = false, string $year_suffix = 'г.'):string
    {
        if ($datetime === "0000-00-00 00:00:00" || $datetime === "0000-00-00") return "-";
        list($y, $m, $d, $h, $i, $s) = sscanf($datetime, "%d-%d-%d %d:%d:%d");

        $rusdate
            = $d
                . ' '
                . self::ruMonths[$m]
                . ($y ? " {$y} {$year_suffix}"
            : '');

        if ($is_show_time) {
            $rusdate .= " " . sprintf("%02d", $h) . ":" . sprintf("%02d", $i);
        }
        return $rusdate;
    }

    public static function convertDateToDayOfMonth(string $datetime):string
    {
        if ($datetime == "0000-00-00 00:00:00" or $datetime == "0000-00-00") return "-";
        list($y, $m, $d, $h, $i, $s) = sscanf($datetime, "%d-%d-%d %d:%d:%d");

        return "<span>{$d}</span>" . self::getMonth($m);
    }

    public static function getMonth(int $index):string
    {
        return self::ruMonths[$index] ?? '';
    }

    public static function convertDatetimeToTimestamp(string $datetime, string $format = 'Y-m-d H:i:s'):int
    {
        return intval(date_format(date_create_from_format($format, $datetime), 'U'));
    }

    public static function convertDateToTimestamp(string $date, string $format = 'Y-m-d'): int
    {
        return intval(date_format(date_create_from_format($format, $date), 'U'));
    }
}

# -eof-
