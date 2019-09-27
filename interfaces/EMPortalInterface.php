<?php


namespace SteamBoat;


interface EMPortalInterface {

    /**
     * EMPortal constructor.
     * @param $token
     */
    public function __construct($token);

    /**
     * @param int $addressId
     * @return bool|string
     */
    public function getDoctors(int $addressId = 0);

    /**
     * @param int $addressId
     * @return bool|string
     */
    public function getClinic(int $addressId = 0);

    /**
     * @param array $fields
     * @return bool|string
     */
    public function createAppointment(array $fields = []);
}