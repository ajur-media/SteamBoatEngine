<?php

namespace SteamBoat;

use Exception;
use Throwable;

/**
 * Class AJURCurrency
 *
 * Не требует инициализации, так как содержит только статические методы
 *
 * @package SteamBoat
 */
class AJURCurrency implements AJURCurrencyInterface
{
    /**
     * Форматирует валюту
     *
     * @param $value
     * @return mixed
     */
    public static function formatCurrencyValue($value)
    {
        return money_format('%i', str_replace(',', '.', $value));

        /*if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
            return NumberFormatter::formatCurrency($value, 'RUR');
        } else {
            return money_format('%i', str_replace(',', '.', $value));
        }*/
    }

    /**
     * Загружает данные о валютах из ЦБР
     *
     * @param null $fetch_date
     * @param string $source
     * @return mixed|null
     * @throws Exception
     */
    public static function getCurrencyData($fetch_date = null, $source = 'CBR')
    {
        $fetch_date = $fetch_date ?? (new \DateTime())->format('d/m/Y');
        $credentials = self::credentials[ $source ];

        $url = $credentials['URL'] . '?' . http_build_query(['date_req' => $fetch_date]);

        $ch = curl_init($url);
        curl_setopt ($ch, CURLOPT_COOKIE, "stay_here=1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS,10);

        $output = curl_exec ($ch);
        curl_close($ch);

        $xml = simplexml_load_string($output);

        $json = json_encode( $xml );
        return json_decode( $json , true );
    }


}