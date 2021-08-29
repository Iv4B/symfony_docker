<?php
namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class GetIpAdressService
{

    public function getIp()
    {
        $client = new HttpClient();

        $response = $client->create(['verify_peer' => false, 'verify_host' => false])->request('GET', 'https://api.ipify.org/');

        return $response->getContent();
    }
}