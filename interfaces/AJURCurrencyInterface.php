<?php


namespace SteamBoat;


interface AJURCurrencyInterface
{
    const credentials = [
        'CBR'   =>  [
            'currencies'    =>  [ 'R01235', 'R01239' ], // usd, euro
            'API_key'       =>  '',
            'URL'           =>  'http://www.cbr.ru/scripts/XML_daily.asp'
        ],
    ];

    public static function formatCurrencyValue($value);

    public static function getCurrencyData($fetch_date = null, $source = 'CBR');

}