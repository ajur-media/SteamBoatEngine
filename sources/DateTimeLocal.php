<?php
/**
 * Created by PhpStorm.
 * User: wombat
 * Date: 04.03.19
 * Time: 15:50
 */

namespace SteamBoat;

interface DateTimeLocalInterface
{
    public static function getMonth(int $index): string;

    public static function convertDate(string $datetime, bool $is_show_time = false, string $year_suffix = 'г.'):string;

    public static function convertDateRu(string $datetime, bool $is_show_time = false, string $year_suffix = 'г.'): string;

    public static function convertDatetimeToTimestamp(string $datetime, string $format = 'd-m-Y H:i:s'): int;

    public static function convertDateToTimestamp(string $date, string $format = 'd-m-Y'): int;
}

class DateTimeLocal implements DateTimeLocalInterface
{
    const VERSION = '1.13';

    // old: tMonth
    public static $tMonth = array(
        '',
        'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'
    );

    // old: $tMonthR
    public static $ruMonths = array(
        1 => 'января', 2 => 'февраля',
        3 => 'марта', 4 => 'апреля', 5 => 'мая',
        6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября',
        12 => 'декабря'
    );

    public static $tWeek = array(
        '',
        'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'
    );

    /**
     * Хелпер, оставлен для совместимости.
     *
     * @param string $datetime
     * @param bool $is_show_time
     * @param string $year_suffix
     * @return string
     */
    public static function convertDateRu(string $datetime, bool $is_show_time = false, string $year_suffix = 'г.'):string
    {
        return self::convertDate($datetime, $is_show_time, $year_suffix);
    }

    /**
     * Берёт дату в формате YYYY-MM-DD и возвращает строку "DD месяца YYYY года"
     *
     * @param string $datetime -- дата YYYY-MM-DD
     * @param bool $is_show_time -- показывать ли время
     * @param string $year_suffix -- суффикс года
     * @return string                   -- та же дата, но по-русски
     */
    public static function convertDate(string $datetime, bool $is_show_time = false, string $year_suffix = 'г.'):string
    {
        if ($datetime == "0000-00-00 00:00:00" or $datetime == "0000-00-00") return "-";
        list($y, $m, $d, $h, $i, $s) = sscanf($datetime, "%d-%d-%d %d:%d:%d");

        $rusdate = $d . ' ' . self::$ruMonths[$m] .
            ($y ? " {$y} {$year_suffix}" : '');

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
        return self::$ruMonths[$index] ?? '';
    }

    /**
     * Конвертирует дату-время в указанном формате в unix timestamp
     *
     * В легаси-коде - convertdt2long
     *
     * @param $datetime
     * @param $format
     *
     * @return false|int
     */
    public static function convertDatetimeToTimestamp(string $datetime, string $format = 'Y-m-d H:i:s'):int
    {
        return intval(date_format(date_create_from_format($format, $datetime), 'U'));
    }

    /**
     * Возвращает timestamp для полуночи указанной даты
     *
     * В легаси-коде convertdt2long_short()
     *
     * @param $date
     * @param string $format
     * @return string
     */
    public static function convertDateToTimestamp(string $date, string $format = 'Y-m-d'): int
    {
        return intval(date_format(date_create_from_format($format, $date), 'U'));
    }
}

