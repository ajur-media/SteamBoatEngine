<?php

namespace SteamBoat;

interface DateTimeLocalInterface
{
    const tMonth = array(
        '',
        'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'
    );

    const ruMonths = array(
        1 => 'января', 2 => 'февраля',
        3 => 'марта', 4 => 'апреля', 5 => 'мая',
        6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября',
        12 => 'декабря'
    );

    const tWeek = array(
        '',
        'понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота', 'воскресенье'
    );

    /**
     * @param int $index
     * @return string
     */
    public static function getMonth(int $index): string;

    /**
     * Берёт дату в формате YYYY-MM-DD и возвращает строку "DD месяца YYYY года"
     *
     * @param string $datetime -- дата YYYY-MM-DD
     * @param bool $is_show_time -- показывать ли время
     * @param string $year_suffix -- суффикс года
     * @return string                   -- та же дата, но по-русски
     */
    public static function convertDate(string $datetime, bool $is_show_time = false, string $year_suffix = 'г.'):string;

    /**
     * Хелпер, оставлен для совместимости.
     *
     * @param string $datetime
     * @param bool $is_show_time
     * @param string $year_suffix
     * @return string
     */
    public static function convertDateRu(string $datetime, bool $is_show_time = false, string $year_suffix = 'г.'): string;

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
    public static function convertDatetimeToTimestamp(string $datetime, string $format = 'd-m-Y H:i:s'): int;

    /**
     * Возвращает timestamp для полуночи указанной даты
     *
     * В легаси-коде convertdt2long_short()
     *
     * @param $date
     * @param string $format
     * @return string
     */
    public static function convertDateToTimestamp(string $date, string $format = 'd-m-Y'): int;

    public static function convertDateToDayOfMonth(string $datetime):string;
}
