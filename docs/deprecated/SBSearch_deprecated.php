<?php

use Arris\AppLogger;
use Foolz\SphinxQL\Drivers\Mysqli\Connection;
use Foolz\SphinxQL\SphinxQL;

class SBSearch_deprecated {

    /**
     * @var Connection
     */
    private static $sphinx_connection_host;
    private static $sphinx_connection_port;

    public static function init(string $sphinx_connection_host, string $sphinx_connection_port)
    {
        self::$sphinx_connection_host = $sphinx_connection_host;
        self::$sphinx_connection_port = $sphinx_connection_port;
    }

    public static function C()
    {
        return self::createConnection();
    }

    public static function createConnection()
    {
        $conn = new Connection();
        $conn->setParams([
            'host' => self::$sphinx_connection_host,
            'port' => self::$sphinx_connection_port
        ]);

        return (new SphinxQL($conn));
    }
    /**
     * Загружает список айдишников из сфинкс-индекса по переданному запросу.
     *
     * @param string $search_query      - строка запроса
     * @param string $source_index      - имя индекса
     * @param string $sort_field        - поле сортировки
     * @param string $sort_order        - условие сортировки
     * @param int $limit                - количество
     * @param array $option_weight      - опции "веса"
     * @return array                    - список айдишников
     */
    public static function get_IDs_DataSet(string $search_query, string $source_index, string $sort_field, string $sort_order = 'DESC', int $limit = 5, array $option_weight = []): array
    {
        $found_dataset = [];
        $compiled_request = '';

        if (empty($source_index)) return $found_dataset;

        try {
            $search_request = self::createConnection()
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
            AppLogger::scope('sphinx')->error(
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