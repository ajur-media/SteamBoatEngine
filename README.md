# SteamBoatEngine

SteamBoat Engine

# Изменения в версии 1.30 

- не будет AjurCurrency 
- не будет AjurWeather
- не будет BBParser
- не будет PSDReader -- https://gist.github.com/devluis/8174317
- не будет EMPortal
- не будет SBLogger

- все пути к каталогам без tailing slash
- удаляем устаревшие функции

? Template class ?

## SBEngine

LogSiteUsage более не запрашивает getenv('LOG_SITE_USAGE'), зато требует наличие этого ключа в списке опций при инициализации:

SBEngine::init(options, logger)

Options: 
- PROJECT_PUBLIC 
- PROJECT_STORAGE
- PROJECT_CLASSES
- STORAGE
- LOG_SITE_USAGE

 

## MySQLWrapper

Теперь вызывается:
```php
new \SteamBoat\MySQLWrapper($config, $pdo_connector, $logger = null);
```

##  Изменения в функциях 

function getDataSetFromSphinx() --> ????????????????????? не реализовано в `Arris\Toolkit\SphinxToolkit::getDatasetIDs()` (добавляем в каждый проект индивидуально, в трейты)

SBCommon::getRandomString       --> SBEngine::getRandomString()
SBCommon::getRandomFilename     --> SBEngine::getRandomFilename()
SBCommon::is_ssl()              --> SBEngine::is_ssl()

getEngineVersion                --> SBEngine::getEngineVersion()
getSiteUsageMetrics             --> SBEngine::getSiteUsageMetrics()
logSiteUsage                    --> SBEngine::logSiteUsage()

simpleSendEMAIL                 --> SBEngine::simpleSendEMAIL()

sanitizeHTMLData                --> SBEngine::sanitizeHTMLData()
normalizeSerialData             --> SBEngine::normalizeSerialData()

unEscapeString                  --> SBEngine::unEscapeString() <-- но вообще её надо выпилить, хз зачем её применяют

## MySQLWrapper

Теперь создается как 
`new MySQLWrapper($_CONFIG['DB_CONNECTIONS']['DATA'], AppLogger::scope('mysql'), DB::C());`

То есть третьим аргументом передается статический коннекшен к БД, например `Arris\DB::C()`. Это обязательный параметр! 
Фактически, это внедрение зависимости. 
 
-------

# ToDo

fix -> Arris\http_redirect
```
public static function redirectCode(string $uri, bool $replace_prev_headers = false, int $code = 302)
    {
        // Функция редиректа с принудительной отсылкой заголовка
        // see also https://gist.github.com/phoenixg/5326222

        $scheme = (self::is_ssl() ? "https://" : "http://");
        $code = array_key_exists($code, self::HTTP_CODES) ? self::HTTP_CODES[$code] : self::HTTP_CODES[302]; /// <---- ADD THIS

        header($code);

        if (strstr($uri, "http://") or strstr($uri, "https://")) {
            header("Location: " . $uri, $replace_prev_headers, $code);
        } else {
            header("Location: {$scheme}" . $_SERVER['HTTP_HOST'] . $uri, $replace_prev_headers, $code);
        }
        exit(0);
    }
```
