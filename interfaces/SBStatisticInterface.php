<?php

namespace SteamBoat;

interface SBStatisticInterface
{
    /**
     * Инициализирует статик-класс
     *
     * @param array $allowed_item_types
     * @param null $logger
     */
    public static function init($allowed_item_types = [], $logger = null);

    /**
     * Готовит данные для отображения библиотекой Morris.JS
     *
     * @param array $data
     * @return array
     */
    public static function prepareDataForMorrisStatview(array $data):array;

    /**
     * Проверяет параметры в $_REQUEST и делает вызов updateVisitCount() с нужными параметрами
     *
     * @NB: Возможно, это костыль, потому что используется для сокращения дублирующегося кода в
     * SteamBoatEngine/ajax_site_stats (легаси)
     * и
     * коллбэке вызова с мобильной версии POST /ajax/stats:updateVisitCount/ { }
     *
     *  {
            id: <int>,
            item_type: <string>,
            cookie_name: <string>
        },
     *
     * @return array
     */
    public static function invoke();

}