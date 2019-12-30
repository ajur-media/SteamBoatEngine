<?php


namespace SteamBoat;


interface SBCommonInterface
{
    const HTTP_CODES = array(
        100 => "HTTP/1.1 100 Continue",
        101 => "HTTP/1.1 101 Switching Protocols",
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        203 => "HTTP/1.1 203 Non-Authoritative Information",
        204 => "HTTP/1.1 204 No Content",
        205 => "HTTP/1.1 205 Reset Content",
        206 => "HTTP/1.1 206 Partial Content",
        300 => "HTTP/1.1 300 Multiple Choices",
        301 => "HTTP/1.1 301 Moved Permanently",
        302 => "HTTP/1.1 302 Found",
        303 => "HTTP/1.1 303 See Other",
        304 => "HTTP/1.1 304 Not Modified",
        305 => "HTTP/1.1 305 Use Proxy",
        307 => "HTTP/1.1 307 Temporary Redirect",
        400 => "HTTP/1.1 400 Bad Request",
        401 => "HTTP/1.1 401 Unauthorized",
        402 => "HTTP/1.1 402 Payment Required",
        403 => "HTTP/1.1 403 Forbidden",
        404 => "HTTP/1.1 404 Not Found",
        405 => "HTTP/1.1 405 Method Not Allowed",
        406 => "HTTP/1.1 406 Not Acceptable",
        407 => "HTTP/1.1 407 Proxy Authentication Required",
        408 => "HTTP/1.1 408 Request Time-out",
        409 => "HTTP/1.1 409 Conflict",
        410 => "HTTP/1.1 410 Gone",
        411 => "HTTP/1.1 411 Length Required",
        412 => "HTTP/1.1 412 Precondition Failed",
        413 => "HTTP/1.1 413 Request Entity Too Large",
        414 => "HTTP/1.1 414 Request-URI Too Large",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        416 => "HTTP/1.1 416 Requested range not satisfiable",
        417 => "HTTP/1.1 417 Expectation Failed",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
        502 => "HTTP/1.1 502 Bad Gateway",
        503 => "HTTP/1.1 503 Service Unavailable",
        504 => "HTTP/1.1 504 Gateway Time-out"
    );

    const DICTIONARY_FULL = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz';

    const DICTIONARY = '0123456789abcdefghijklmnopqrstuvwxyz';

    /**
     * Генерация рэндомных строк
     *
     * @param $length
     * @return string
     */
    public static function getRandomString(\int $length):\string;

    /**
     * Генерируем новое имя файла на основе даты.
     * Формат: ПРЕФИКС + СОЛЬ + СУФФИКС
     *
     * ПРЕФИКС делается на основе текущей (!) даты по переданной маске. Если маска пустая - префикс пуст
     * СОЛЬ - случайная строка [a-z0-9]
     * СУФФИКС - строка, если не пуста - заменяется на "{суффикс}"
     *
     * @param int $length - длина соли
     * @param string $suffix - суффикс имени файла.
     * @param string $prefix_format - формат даты в префиксе (Ymd)
     * @return string
     */
    public static function getRandomFilename(\int $length = 20, \string $suffix = '', $prefix_format = 'Ymd'):\string;

    /**
     * Функция редиректа с принудительной отсылкой заголовка
     * see also https://gist.github.com/phoenixg/5326222
     *
     * @param $uri
     * @param bool $replace_prev_headers
     * @param int $code
     */
    public static function redirectCode(\string $uri, \bool $replace_prev_headers = false, \int $code = 302);

    /**
     * Проверяет, обратились ли к текущему скрипту через SSL
     * @return bool
     */
    public static function is_ssl():\bool;
}