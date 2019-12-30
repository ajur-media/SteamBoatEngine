<?php

namespace SteamBoat;

use Exception;

interface AJURCurrencyInterface
{
    const credentials = [
        'CBR'   =>  [
            'currencies'    =>  [ 'R01235', 'R01239' ], // usd, euro
            'API_key'       =>  '',
            'URL'           =>  'http://www.cbr.ru/scripts/XML_daily.asp'
        ],
    ];

    /**
     * Форматирует валюту
     *
     * @param $value
     * @return mixed
     */
    public static function formatCurrencyValue($value);

    /**
     * Загружает данные о валютах из ЦБР
     *
     * @param null $fetch_date
     * @param string $source
     * @return mixed|null
     * @throws Exception
     */
    public static function getCurrencyData($fetch_date = null, $source = 'CBR');

}