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
    function getfixedpicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;
    function resizeimageaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;
    function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;
    function resizepictureaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool;
}

if (!function_exists('getfixedpicture')) {

    /**
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     * @throws Exception
     */
    function getfixedpicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool
    {
        return GDWrapper::getFixedPicture($fn_source, $fn_target, $maxwidth, $maxheight);
    }
}

if (!function_exists('resizeimageaspect')) {

    /**
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     */
    function resizeimageaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool
    {
        return GDWrapper::resizeImageAspect($fn_source, $fn_target, $maxwidth, $maxheight);
    }
}

if (!function_exists('verticalimage')) {

    /**
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     * @throws Exception
     */
    function verticalimage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool
    {
        return GDWrapper::verticalimage($fn_source, $fn_target, $maxwidth, $maxheight);
    }
}

if (!function_exists('resizepictureaspect')) {

    /**
     * @param $fn_source
     * @param $fn_target
     * @param $maxwidth
     * @param $maxheight
     * @return bool
     * @throws Exception
     */
    function resizepictureaspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight):bool
    {
        return GDWrapper::resizePictureAspect($fn_source, $fn_target, $maxwidth, $maxheight);
    }
}

if (!function_exists('getDataSetFromSphinx')){

    /**
     * Загружает список айдишников из сфинкс-индекса по переданному запросу.
     *
     * Old implementation is `\SteamBoat\SBSearch::get_IDs_DataSet`
     *
     * @param string $search_query      - строка запроса
     * @param string $source_index      - имя индекса
     * @param string $sort_field        - поле сортировки
     * @param string $sort_order        - условие сортировки
     * @param int $limit                - количество
     * @param array $option_weight      - опции "веса"
     * @return array                    - список айдишников
     */
    function getDataSetFromSphinx(string $search_query, string $source_index, string $sort_field, string $sort_order = 'DESC', int $limit = 5, array $option_weight = []): array
    {
        $found_dataset = [];
        $compiled_request = '';
        if (empty($source_index)) return $found_dataset;
        try {
            $search_request = \Arris\Toolkit\SphinxToolkit::createInstance()
                ->select()
                ->from($source_index);
            if (!empty($sort_field)) {
                $search_request = $search_request
                    ->orderBy($sort_field, $sort_order);
            }
            if (!empty($option_weight)) {
                $search_request = $search_request
                    ->option('field_weights', $option_weight);
            }
            if (!is_null($limit) && is_numeric($limit)) {
                $search_request = $search_request
                    ->limit($limit);
            }
            if (strlen($search_query) > 0) {
                $search_request = $search_request
                    ->match(['title'], $search_query);
            }
            $search_result = $search_request->execute();
            while ($row = $search_result->fetchAssoc()) {
                $found_dataset[] = $row['id'];
            }
        } catch (Exception $e) {
            \Arris\AppLogger::scope('sphinx')->error(
                __CLASS__ . '/' . __METHOD__ .
                " Error fetching data from `{$source_index}` : " . $e->getMessage(),
                [
                    htmlspecialchars(urldecode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                    $search_request->getCompiled(),
                    $e->getCode()
                ]
            );
        }
        return $found_dataset;
    } // get_IDs_DataSet()
}



# -eof-

if (!function_exists('_')){
    function _()
    {

    }
}