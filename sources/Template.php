<?php

namespace SteamBoat;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Smarty;

/**
 * Class Template
 * @package SteamBoat
 *
 */
class Template implements TemplateInterface
{
    /**
     * @var Smarty
     */
    public static $smarty;

    /**
     * @var \stdClass
     */
    public static $steamboat_logic_instance;

    /**
     * @var array
     */
    public static $banners;

    /**
     * @var array
     */
    public static $response;

    /**
     * @var string
     */
    public static $title_delimeter;

    /**
     * @var string
     */
    public static $search_mask_puid40;
    /**
     * @var Logger|null
     */
    private static $logger;

    /*
     * META-теги
     */
    public static $meta = [
        'title'         =>  '',
        'keywords'      =>  '',
        'description'   =>  ''
    ];

    /**
     * @var array массив заголовков
     */
    public static $_title = [];

    /**
     * @var string
     */
    public static $ajur_adv_topic;

    /**
     * Биндить ли ADFOX-PUID40? [false]
     * @var
     */
    private static $bind_puid40;

    /* ============================================================================================================== */

    public static function init($smarty, $that = null, $options = [], LoggerInterface $logger = null)
    {
        self::$title_delimeter = " " . SBEngine::setOption($options, 'title_delimeter', '&#8250;') . " ";
        self::$search_mask_puid40 = SBEngine::setOption($options, 'search_mask_puid40', '<!--#echo var="ADVTOPIC"-->');
        self::$bind_puid40 = SBEngine::setOption($options, 'bind_puid40', false);

        self::$logger
            = $logger instanceof LoggerInterface
            ? $logger
            : new NullLogger();
    
        self::$smarty = $smarty;
        self::$steamboat_logic_instance = $that;

        self::$banners = [];
        self::$response = [
            'status'    =>  null,
            'mode'      =>  'HTML',
            'data'      =>  [
                'page'      =>  1
            ],
            'html'      =>  '',
        ];
    }

    public static function assign($variable, $value, $nocache = false)
    {
        //@todo: А что насчет NESTED-variables? ('menu.opened' к примеру) ?
        self::$smarty->assign($variable, $value);
    }

    public static function addMeta($variable, $value)
    {
        self::$meta[ $variable ] = $value;
    }

    public static function getMeta()
    {
        return self::$meta;
    }

    public static function addTitle($title)
    {
        self::$_title[] = $title;
    }

    public static function getTitle():string
    {
        // преобразует кавычки-лапки в html-entities
        array_walk(Template::$_title, function ($t){
            self::escapeQuotes($t);
        });

        return implode(self::$title_delimeter, array_reverse(Template::$_title));
    }

    public static function setResponseMode($mode = 'HTML')
    {
        self::$response['mode']
            = (in_array($mode, ['HTML', 'JSON', 'AJAXHTML', 'AJAX']))
            ? strtoupper($mode)
            : 'HTML';
    }

    public static function render()
    {
        if (self::$response['mode'] === 'JSON') {

            self::$response['html'] = self::$smarty->fetch("__ajax_template.tpl");
            self::$response['status'] = 'ok';

            return json_encode(self::$response);

        } elseif(self::$response['mode'] === 'AJAX' || self::$response['mode'] === 'AJAXHTML') {

            self::$response['html'] = self::$smarty->fetch("__ajax_template.tpl");
            self::$response['status'] = 'ok';

            return self::$response['html'];

        } else {

            self::$response['html'] = self::$smarty->fetch("__main_template.tpl");

            self::bindBanners();

            return self::$response['html'];
        }
    }

    public static function bindTopic($value)
    {
        self::$ajur_adv_topic = $value;
    }

    /**
     * Заменяет в потоке данных в баннерах замещаемую переменную на значение поля $ajur_adv_topic
     *
     * Вызывается в методе render() для response-type == 'HTML'
     *
     * Если нам нужно будет подменять эти значения для других типов отдаваемого контента - нужно
     * добавить вызов в соотв. блоки
     */
    private static function bindBanners()
    {
        if (self::$bind_puid40) {
            self::$response['html'] = str_replace(self::$search_mask_puid40, self::$ajur_adv_topic, self::$response['html']);
        }
    }

    /**
     * Заменяет кавычки-лапки на html-entities
     *
     * @param $string
     * @return mixed
     */
    private static function escapeQuotes($string)
    {
        return str_replace(['«', '»'], ['&laquo;', '&raquo;'], $string);
    }

}

# -eof-
