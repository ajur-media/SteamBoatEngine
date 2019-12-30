<?php

namespace SteamBoat;

/**
 * Class EMPortal
 * @package SteamBoat
 *
 * Логгирование: не используется
 */
class EMPortal implements EMPortalInterface
{
    const VERSION = "1.11";

    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function getDoctors(int $addressId = 0)
    {
        $url = "https://emportal.ru/api/v1/doctors?json=1&addressId={$addressId}";
        return $this->sendCurlRequest($url, 'GET');
    }

    /**
     *
     * @param $url
     * @param $type
     * @param null $fields
     * @return bool|string
     */
    private function sendCurlRequest($url, $type, $fields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_USERPWD, "user:" . $this->token);
        switch ($type) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
                break;
            default:
                break;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        $server_output = curl_exec($ch);
        //curl_error($ch);
        curl_close($ch);

        return $server_output;
    }

    public function getClinic(int $addressId = 0)
    {
        $url = "https://emportal.ru/api/v1/addresses?json=1&id={$addressId}";
        return $this->sendCurlRequest($url, 'GET');
    }

    public function createAppointment(array $fields = [])
    {
        $url = "https://emportal.ru/api/v1/appointments";
        return $this->sendCurlRequest($url, 'POST', $fields);
    }
}

# -eof-
