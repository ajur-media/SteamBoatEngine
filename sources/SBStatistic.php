<?php

namespace SteamBoat;

use Exception;
use Psr\Log\LoggerInterface;
use function Arris\DBC;
use function Arris\DBC as DBCAlias;

/**
 * Class SBStatistic
 *
 * Dr. Calculus statistics engine
 *
 * @todo: move to AstolfoEngine
 *
 * @package SteamBoat
 */
class SBStatistic implements SBStatisticInterface
{
    /**
     * @var array
     */
    private static $allowed_item_types = [];

    /**
     * @var LoggerInterface|null
     */
    private static $logger;

    private static $is_engine_disabled = false;

    public static function init($allowed_item_types = [], $logger = null)
    {
        if (!empty($allowed_item_types)) {
            self::$allowed_item_types = $allowed_item_types;
        }

        if ($logger instanceof LoggerInterface) {
            self::$logger = $logger;
        }

        self::$is_engine_disabled = getenv('DEBUG.DISABLE_DRCALCULUS_STATS_ENGINE');
    }

    public static function prepareDataForMorrisStatview(array $data):array
    {
        if (empty($data)) return [];

        $export = [];
        $visit_total = 0;
        foreach ($data as $row) {
            $export[] = [
                'date'  =>  date('d.m.Y', strtotime($row['event_date'])),
                'value' =>  $row['event_count']
            ];
            $visit_total += $row['event_count'];
        }

        return [ $export, $visit_total ];
    }

    public static function invoke()
    {
        try {
            if (self::$is_engine_disabled)
                throw new \Exception('Dr.Calculus stats engine not ready', 999);

            $id = intval($_REQUEST['id']);

            if (intval($_REQUEST['id']) == 0)
                throw new Exception('Неправильный ID элемента', 0);

            $item_type = $_REQUEST['item_type'];
            if (!in_array($item_type, self::$allowed_item_types))
                throw new Exception('Неправильный ID семейства', 1);

            $cookie_prefix = $_REQUEST['cookie_name'];

            if (isset($_SESSION[ $cookie_prefix ][ $id ]))
                throw new Exception('Страницу уже посещали', 2);

            $updateState = self::updateVisitCount($id, $item_type);

            if (!$updateState['state'])
                throw new Exception("Ошибка вставки данных в БД", 3);

            $response = [
                'id'    =>  $id,
                'type'  =>  $item_type,
                'status'=>  'ok',
                'message'=> 'ok',
                'lid'   =>  $updateState['lid'],
            ];
        } catch (Exception $e) {
            $response = [
                'status'    =>  'error',
                'message'   =>  $e->getMessage(),
                'errorCode' =>  $e->getCode(),
                'errorMsg'  =>  $e->getMessage()
            ];
        }

        return $response;
    }

    /**
     * Обновляет таблицу статистики.
     *
     * @param $item_id   - id сущности
     * @param $item_type - тип сущности (значение из словаря, заданного при инициализации)
     * @throws Exception
     * @return array     - [ 'state' => статус, 'lid' => id вставленного/обновленного элемента ]
     */
    public static function updateVisitCount($item_id, $item_type)
    {
        $sql_query = "
 INSERT INTO
    stat_nviews
SET
    `item_id` = :item_id,
    `item_type` = :item_type,
    `event_count` = 1,
    `event_date` = NOW()
ON DUPLICATE KEY UPDATE
    `event_count` = `event_count` + 1;
        ";

        $sth = DBCAlias()->prepare($sql_query);
        $r = $sth->execute([
            'item_id'   =>  $item_id,
            'item_type' =>  $item_type,
        ]);
        return [
            'state'     =>  $r,
            'lid'       =>  DBCAlias()->lastInsertId()
        ];
    }

    /**
     * Возвращает количество посещений материала по ID и типу
     *
     * @param int $id
     * @param string $type
     * @param int|null $interval -- null - за всю историю, иначе за последние N дней
     * @throws Exception
     * @return array
     */
    public static function getItemViewCount(int $id, string $type, $interval = null)
    {
        if (!in_array($type, self::$allowed_item_types)) return [];

        // значения для плейсхолдеров в запросе
        $sql_conditions = [
            'id'    =>  $id,
            'type'  =>  $type
        ];

        // длиннее, но
        if ($interval !== null && is_numeric($interval)) {
            // добавляем AND event_date between date(now() - interval :days day) and date(now())

            $sql = "
SELECT * FROM stat_nviews 
WHERE item_type = :type 
  AND item_id = :id 
  AND event_date between date(now() - interval :days day) and date(now())
ORDER BY event_date ASC                  
            ";

            // и значение в плейсхолдер
            $sql_conditions['days'] = $interval;

        } else {

            $sql = "
SELECT * FROM stat_nviews 
WHERE item_type = :type 
  AND item_id = :id 
ORDER BY event_date ASC                  
            ";

        }

        $sth = DBC()->prepare($sql);
        $sth->execute($sql_conditions);

        return $sth->fetchAll();
    }

    /**
     * Хелпер-функция: посещений сущности сегодня
     *
     * @param $item_id
     * @param $item_type
     * @return mixed
     * @throws Exception
     */
    public static function getVisitCountToday($item_id, $item_type)
    {
        $sql_query = "
SELECT `event_count` 
FROM `stat_nviews`
WHERE `item_id` = :item_id AND `item_type` = :item_type AND `event_date` = NOW()
        ";
        $sth = DBCAlias()->prepare($sql_query);
        $sth->execute([
            'item_id'   =>  $item_id,
            'item_type' =>  $item_type
        ]);

        return $sth->fetchColumn();
    }

    /**
     * Хелпер-функция: посещений сущности всего
     *
     * @param $item_id
     * @param $item_type
     * @return mixed
     * @throws Exception
     */
    public static function getVisitCountTotal($item_id, $item_type)
    {
        $sql_query = "
SELECT SUM(`event_count`) 
FROM `stat_nviews`
WHERE `item_id` = :item_id and `item_type` = :item_type;
        ";
        $sth = DBCAlias()->prepare($sql_query);
        $sth->execute([
            'item_id'   =>  $item_id,
            'item_type' =>  $item_type
        ]);

        return $sth->fetchColumn();
    }

    /**
     * Функция-хелпер: посещений сущности суммарно: всего и сегодня
     *
     * @param $item_id
     * @param $item_type
     * @return array array ['total', 'today']
     * @throws Exception
     */
    public static function getVisitCountTodaySummary($item_id, $item_type)
    {
        $sql_query = "
SELECT `event_count`, `event_date` 
FROM `stat_nviews`
WHERE `item_id` = :item_id and `item_type` = :item_type
ORDER BY `event_date` DESC 
        ";
        $sth = DBCAlias()->prepare($sql_query);
        $sth->execute([
            'item_id'   =>  $item_id,
            'item_type' =>  $item_type
        ]);

        $row = $sth->fetch();
        $result = [
            'total' =>  $row['count'],
            'today' =>  $row['count']
        ];
        while ($row = $sth->fetch()) {
            $result['total'] += $row['count'];
        }
        return $result;
    }


}

# -eof-
