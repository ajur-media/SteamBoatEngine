<?php

namespace SteamBoat;

use PDO;
use PDOStatement;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * @todo: -> Arris package
 *
 * Class PDOWrapper
 * @package SteamBoat
 */
class PDOWrapper implements PDOWrapperInterface
{
    /**
     * @var PDO
     */
    public static $dbh;
    
    /**
     * @var PDOStatement
     */
    public static $sth;
    /**
     * @var LoggerInterface
     */
    private static $logger;
    
    /**
     * @var float
     */
    private static $slow_query_threshold;
    
    private static $mysqlcountquery;
    /**
     * @var float|string
     */
    private static $mysqlquerytime;
    
    /* =========================================================== */
    
    public static function init($pdo_connector, $options = [], LoggerInterface $logger = null)
    {
        self::$dbh = $pdo_connector;
        self::$logger = $logger;
    
        if (array_key_exists('slow_query_threshold', $options)) self::$slow_query_threshold = (float)$options['slow_query_threshold'];
    }
    
    public static function query(string $query, array $dataset)
    {
        $time_start = microtime(true);
        
        self::$sth = self::$dbh->prepare($query);
        
        foreach ($dataset as $key => $value) {
            if (is_array($value)) {
                $type = (count($value) > 1) ? $value[1] : PDO::PARAM_STR;
                
                self::$sth->bindValue($key, $value[0], $type);
            } else {
                self::$sth->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $execute_result = self::$sth->execute();
        
        $time_consumed = microtime(true) - $time_start;
        
        if (!$execute_result) {
            self::$logger->error("PDO::execute() error: ", [
                ((PHP_SAPI === "cli") ? __FILE__ : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                self::$dbh->errorInfo(),
                $query
            ]);
        }
        
        if (($time_consumed > self::$slow_query_threshold)) {
            self::$logger->info("PDO::execute() slow: ", [
                $time_consumed,
                ((PHP_SAPI === "cli") ? __FILE__ : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                $query
            ]);
        }
        
        self::$mysqlcountquery++;
        self::$mysqlquerytime += $time_consumed;
        
        return $execute_result;
    }
    
    public static function result()
    {
        return self::$sth;
    }
    
    public static function fetch($row = 0)
    {
        return (self::$sth instanceof PDOStatement) ? (self::$sth->fetchAll())[$row] : [];
    }
    
    public static function fetchRow($row = 0)
    {
        return (self::$sth instanceof PDOStatement) ? (self::$sth->fetchAll())[$row] : [];
    }
    
    public static function fetchColumn($column = 0, $default = null)
    {
        return (self::$sth instanceof PDOStatement) ? (self::$sth->fetchColumn($column)) : $default;
    }
    
    public static function fetchAll()
    {
        return (self::$sth instanceof PDOStatement) ? self::$sth->fetchAll() : [];
    }
    
    public static function fetchAllCallback($class = null)
    {
        if (is_string($class) || ($class instanceof stdClass)) {
            return (self::$sth instanceof PDOStatement) ? self::$sth->fetchAll(PDO::FETCH_CLASS, $class) : [];
        }
    
        if (is_null($class)) {
            return (self::$sth instanceof PDOStatement) ? self::$sth->fetchAll() : [];
        }
    
        return [];
    }
    
    public static function lastInsertID($name = null):string
    {
        return self::$dbh->lastInsertId($name);
    }
    
    public static function getStatistic()
    {
        return [ self::$mysqlcountquery, self::$mysqlquerytime ];
    }
    
    
}