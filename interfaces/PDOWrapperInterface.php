<?php

namespace SteamBoat;

use Psr\Log\LoggerInterface;

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
    
    public static function fetchColumn($column = 0, $default = null);
    
    public static function fetchAll();
    
    public static function lastInsertID();
    
    public static function getStatistic();
    
}