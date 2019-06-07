<?php


namespace SteamBoat;

use Exception;
use mysqli_result;
use Arris\AppLogger;

interface MySQLWrapperInterface {
    public function __construct($config, $suffix = 'DATA');
    public function connect();
    public function close();
    public function query($query, $log_sql_request = false);
    public function multi_query($query, $debug = false);
    public function result($res, $row);
    public function fetch($result);
    public function num_rows($res);
    public function insert_id();
    public function create($fields, $table, $hash = null, $joins = null, $needpages = true);
}

class MySQLWrapper implements MySQLWrapperInterface
{
    const VERSION = '1.17.15';

    const DEFAULT_CHARSET = 'utf8';

    const DEFAULT_CHARSET_COLLATE = 'utf8_general_ci';

    public $connection_instantiator = null;

    public $hostname;
    public $username;
    public $password;
    public $database;
    public $port;

    public $charset;
    public $charset_collate;

    // счетсчик кол-ва запросов в базу
    public $mysqlcountquery = 0;

    // счетчик общего времени запросов в базу
    public $mysqlquerytime = 0;

    public $result;

    public $db;

    /**
     * @var array
     */
    public $db_config = [];

    public $total = 0;
    public $pages = array();

    /**
     * @var bool MySQLi Request Error
     */
    public $request_error = false;

    /**
     * mysql constructor.
     *
     * @param $config
     * @param string $suffix
     * @throws Exception
     */
    public function __construct($config, $suffix = 'DATA')
    {
        if (!array_key_exists($suffix, $config['DB_CONNECTIONS'])) {
            AppLogger::scope('mysql')->emergency('[MYSQL ERROR] at '. __CLASS__ . '->' . __METHOD__ .' : DB_CONNECTIONS collection does not contain given suffix', [$suffix]);
            die('[MYSQL ERROR] at '. __CLASS__ . '->' . __METHOD__ .' : DB_CONNECTIONS collection does not contain given suffix : `' . $suffix . '`');
        }

        $this->db_config = $config['DB_CONNECTIONS'][ $suffix ];
        $this->connection_instantiator = $suffix;

        $this->hostname = $this->db_config['hostname'];
        $this->port     = $this->db_config['port'];
        $this->username = $this->db_config['username'];
        $this->password = $this->db_config['password'];
        $this->database = $this->db_config['database'];

        if (!array_key_exists('charset', $this->db_config)) {
            $this->charset = self::DEFAULT_CHARSET;
        } elseif(!is_null($this->db_config['charset'])) {
            $this->charset = $this->db_config['charset'];
        } else {
            $this->charset = null;
        }

        if (!array_key_exists('charset_collate', $this->db_config)) {
            $this->charset_collate = self::DEFAULT_CHARSET_COLLATE;
        } elseif(!is_null($this->db_config['charset_collate'])) {
            $this->charset_collate = $this->db_config['charset_collate'];
        } else {
            $this->charset_collate = null;
        }

        $this->connect();
    }

    /**
     * Коннект к базе
     */
    public function connect()
    {
        $this->db = mysqli_connect($this->hostname, $this->username, $this->password, $this->database, $this->port);

        if (mysqli_connect_error()) {

            AppLogger::scope('mysql')->emergency('[MYSQL ERROR] ', [ mysqli_connect_errno(), mysqli_connect_error(), $this->db_config ]);

            die(
                '['
                .mysqli_connect_errno()
                .'] Ошибка подключения '
                . mysqli_connect_error()
                . " ( Host: {$this->hostname}; Port: {$this->port}; User: {$this->username}; Database: {$this->database}"
            );
        }

        if ($this->charset) {
            mysqli_query($this->db, "SET CHARACTER SET utf8");
            mysqli_query($this->db, "SET NAMES utf8");
            mysqli_query($this->db, "set character_set_server='utf8'");
            mysqli_query($this->db, "set character_set_results='utf8'");
            mysqli_query($this->db, "set character_set_connection='utf8'");
        }
        if ($this->charset_collate) {
            mysqli_query($this->db, "SET SESSION collation_connection='utf8_general_ci'");
        }
    }

    public function close()
    {
        mysqli_close($this->db);
    }

    // запрос в базу
    public function query($query, $log_sql_request = false)
    {
        if ($log_sql_request)
            AppLogger::scope('mysql')->debug('[MYSQL QUERY]', [$query]);

        $error = false;
        $this->request_error = false;

        $this->mysqlcountquery++;

        $time_start = microtime(true);

        if (!$result = mysqli_query($this->db, $query)) {
            $error = true;
            $this->request_error = true;
        }

        $time_finish = microtime(true);
        $time_consumed = $time_finish - $time_start;

        if ($error) {
            AppLogger::scope('mysql')->error("mysqli_query() error: ", [
                ((php_sapi_name() == "cli") ? __FILE__ : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                mysqli_error($this->db),
                $query
            ]);
        }

        if (($time_consumed > getenv('DB_SLOW_QUERY_THRESHOLD'))) {
            AppLogger::scope('mysql')->info("mysqli_query() slow: ", [
                $time_consumed,
                ((php_sapi_name() == "cli") ? __FILE__ : ($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                $query
            ]);
        }

        $this->mysqlquerytime += $time_consumed;
        $this->result = $result;
        return $result;
    }

    // запрос в базу
    public function multi_query($query, $debug = false)
    {
        $this->mysqlcountquery++;

        $time_start = microtime(true);

        $result = mysqli_multi_query($this->db, $query) or die ("Mysql error: " . mysqli_error($this->db) . "<br />Mysql query: " . $query . "");

        $time_finish = microtime(true);
        $time_consumed = $time_start - $time_finish;
        $this->mysqlquerytime += $time_consumed;
        $this->result = $result;

        do {
            // do nothing, just iterate over results to make sure no errors
        } while (mysqli_next_result($this->db));
        return $result;
    }

    public function result($res, $row)
    {
        $r = mysqli_fetch_array($res);
        return $r[$row];
    }

    public function fetch($result)
    {
        if (is_null($result) and isset($this->result)) {

            if (! $this->result instanceof mysqli_result) {
                AppLogger::scope('mysql')->error(__METHOD__ . " tries to execute mysqli_fetch_assoc() on boolean, stack trace: ", [ debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)]);
                return null;
            } else {
                return mysqli_fetch_assoc($this->result);
            }

        } else {

            if (! $result instanceof mysqli_result) {
                AppLogger::scope('mysql')->error(__METHOD__ . " tries to execute mysqli_fetch_assoc() on boolean, stack trace: ", [ debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)]);
                return null;
            } else {
                return mysqli_fetch_assoc($result);
            }
        }
    }

    public function num_rows($res)
    {
        return mysqli_num_rows($res);
    }

    public function insert_id()
    {
        return mysqli_insert_id($this->db);
    }

    public function create($fields, $table, $hash = null, $joins = null, $needpages = true)
    {
        $where = "";
        $dwhere = "";
        $limit = "";
        $perpage = 0;
        $own_cond = "";
        $having = "";
        $force_index = "";
        $custom_condition = [];

        if (array_key_exists('custom_condition', $hash)) {
            $custom_condition = $hash['custom_condition'];
            unset($hash['custom_condition']);
        }

        if (isset($hash['having'])) {
            $having = $hash['having'];
        }

        if (isset($hash['own_cond'])) {
            $own_cond = $hash['own_cond'];
        }

        if (isset($hash['force_index'])) {
            $force_index = $hash['force_index'];
        }

        if (isset($hash['perpage']) and is_numeric($hash['perpage'])) {
            $perpage = $hash['perpage'];
        }
        unset($hash['perpage']);

        if (isset($hash['page']) and is_numeric($hash['page']) and isset($hash['limit'])) {
            $from = (ceil($hash['page']) - 1) * $hash['limit'];
            $limit = "LIMIT " . $from . ", " . $hash['limit'];
        }

        if (isset($hash['limit']) and is_numeric($hash['limit']) and !isset($hash['page'])) {
            $limit = "LIMIT " . ceil($hash['limit']);
        }

        // все записи
        if (isset($hash['limit']) and $hash['limit'] == "all" and !isset($hash['page'])) {
            $limit = "";
        }

        $order = "";
        if (isset($hash['order'])) {
            $order = "ORDER BY " . $hash['order'];
        }

        $group = "";
        if (isset($hash['group'])) {
            $group = "GROUP BY " . $hash['group'];
        }

        unset($hash['limit'], $hash['page'], $hash['order'], $hash['own_cond'], $hash['having'], $hash['force_index'], $hash['group']);

        if (is_array($hash))

            foreach ($hash as $key => $value) {
                if (is_array($value) and (isset($value['from']) or isset($value['to']))) {
                    // тип выбора "от" и "до"
                    if (isset($value['from']) and strlen($value['from']) > 0 and !isset($value['to']))
                        $where .= " AND {$key}>=" . $value['from'] . "";

                    if (!isset($value['from']) and isset($value['to']) and strlen($value['to']) > 0)
                        $where .= " AND {$key}<=" . $value['to'] . "";

                    if (isset($value['from']) and isset($value['to']) and strlen($value['to']) > 0 and strlen($value['from']) > 0)
                        $where .= " AND {$key}<=" . $value['to'] . " AND {$key}>=" . $value['from'] . "";

                } elseif (is_array($value) and (isset($value['like']) and $value['like'] == 1 and isset($value['string']))) {
                    // LIKE
                    $swhere = array();
                    if (is_array($value['fields'])) {
                        foreach ($value['fields'] as $searchfield) {
                            $swhere[] = "{$searchfield} LIKE '%" . $value['string'] . "%'";
                        }
                    }
                    $where .= " AND (" . join(" OR ", $swhere) . ")";

                } elseif (is_array($value) and isset($value['operand'])) {

                    if (is_array($value['value'])) {
                        foreach ($value['value'] as $vvv) {
                            $where .= " AND {$key} {$value['operand']} " . $vvv . "";
                        }
                    } else {
                        $where .= " AND {$key} {$value['operand']} " . $value['value'] . "";
                    }

                } elseif (is_array($value) and isset($value['or'])) {

                    if (is_array($value['value'])) {
                        $lll = array();
                        foreach ($value['value'] as $k => $vvv) {
                            $lll[] = "{$k} = {$vvv}";
                        }
                        $where .= " AND (" . implode(" OR ", $lll) . ")";
                    }

                } elseif (is_array($value)) {
                    // множественный выбор
                    $c = "";
                    if (strstr($key, "!")) {
                        $key = str_replace("!", "", $key);
                        $c = " NOT ";
                    }
                    $where .= " AND {$key} {$c} IN (" . implode(", ", $value) . ")";

                } else {

                    $c = "";
                    if (strstr($key, "!")) {
                        $key = str_replace("!", "", $key);
                        $c = "!";
                    }
                    $where .= " AND {$key} {$c}= '" . $value . "'";

                }
            }

        if (count($custom_condition) > 0) {
            $where_custom_condition = implode(' AND ', $custom_condition);
            $where .= ' AND ' . $where_custom_condition;
        } elseif (isset($own_cond) and strlen($own_cond) > 0) {
            $where .= " AND " . $own_cond;
        }

        $where_pages = $where;

        $query = "SELECT {$fields} FROM {$table}";

        if (isset($force_index) and strlen($force_index) > 0) {
            $query .= PHP_EOL . " FORCE INDEX ({$force_index})";
        }

        if (is_array($joins)) {
            foreach ($joins as $key => $value) {
                $query .= PHP_EOL . " LEFT JOIN {$key} ON ({$value})";
            }
        }

        $query
            .= " WHERE 1=1 {$where} "
            . ((isset($having) and strlen($having) > 0) ? "HAVING {$having}" : "")
            . " {$group} {$order}";

        $query_pages = "SELECT COUNT(*) FROM {$table}";

        if (is_array($joins)) {
            foreach ($joins as $key => $value) {
                $query_pages .= PHP_EOL . " LEFT JOIN {$key} ON ({$value})";
            }
        }

        $query_pages .= PHP_EOL . " WHERE 1=1 {$where_pages} {$group} ";

        $limit = ' ' . $limit;

        if ($needpages) {
            $res = $this->query($query_pages);
            $this->total = mysqli_num_rows($res);

            $tmp = mysqli_fetch_field($res);

            if ($this->total == 1 and ($tmp->name == " COUNT(*) ")) {
                $this->total = $this->result($res, 0);
            }

            $this->pages = array();

            if ($perpage > 0) {
                for ($i = 1; $i <= ceil($this->total / $perpage); $i++) {
                    $this->pages[] = $i;
                }
            }
        }
        $this->query = $query . $limit;
        return $query . $limit;
    }

}