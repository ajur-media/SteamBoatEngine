# Init

```
GDWrapper::init($options = [], $logger = null)
```

* `$options` - Опции  передаются через ассоциативный массив. Сейчас возможен единственный ключ:
    * `JPEG_COMPRESSION_QUALITY` - уровень сжатия оригинальных JPEG-ов, [1..100], по умолчанию - 100.
    * `WEBP_COMPRESSION_QUALITY` - уровень сжатия WEBP, [1..100], по умолчанию 80.

* `$logger` - экземпляр `\Monolog\Logger`, например скоуп

Передача в качестве опций `[]` означает, что будут приняты значения по умолчанию:
* `JPEG_COMPRESSION_QUALITY` = 100
* `WEBP_COMPRESSION_QUALITY` = 80

Пример инициализации:

```
AppLogger::addScope('gdwrapper', [ 'gd_error.log' , Logger::ERROR, 'enabled' => getenv('LOGGING.GDWRAPPER_ERRORS'));

SteamBoat\GDWrapper::init([], AppLogger::scope('gdwrapper'));
```
Логгер передается всегда, но используется ли он - зависит от переменной окружения `LOGGING.GDWRAPPER_ERRORS`

Впрочем, можно использовать deferred-логгер. 

Используются один уровень логгирования:

* `error` - если обрабатываемый файл не существует. Записывется лог, возвращается false.

