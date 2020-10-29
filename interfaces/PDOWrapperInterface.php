<?php

namespace SteamBoat;

use Psr\Log\LoggerInterface;
use stdClass;

interface PDOWrapperInterface
{
    public static function init($pdo_connector, $options = [], LoggerInterface $logger = null);
    
    public static function query(string $query, array $dataset);
    
    public static function result();
    
    public static function fetch($row = 0);
    
    /**
     * Alias to fetch()
     *
     * @param int $row
     * @return mixed
     */
    public static function fetchRow($row = 0);
    
    /**
     * @param int $column
     * @param null $default
     * @return mixed
     */
    public static function fetchColumn($column = 0, $default = null);
    
    /**
     * @return mixed
     */
    public static function fetchAll();
    
    /**
     * EXPERIMENTAL
     *
     * FetchAllCallback to 2d array or array of class instances
     *
     * Howto use with class:
     * ```
     * $users = PDOWrapper::fetchAll(stdClass); // is array of stdClass instances
     *
     * $ln = array_map(function($row) {
     *    $n = clone $row;
     *    $n->ipv4 = long2ip($n->ipv4_long);
     *    return (array)$n; //typecast whole object to array (we can use: return [ 'a' => $n->field_a, ... ]
     *    }, $users);
     * var_dump($ln); // is 2d-array
     * ```
     *
     * @todo: возможно, typehint stdClass является ошибкой и не позволит передавать предопределенные классы в метод
     *
     * @param stdClass $class
     * @return mixed
     */
    public static function fetchAllCallback(stdClass $class);
    
    /**
     * @param null $name
     * @return mixed
     */
    public static function lastInsertID($name = null):string;
    
    /**
     * @return array
     */
    public static function getStatistic();
    
}