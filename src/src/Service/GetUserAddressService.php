<?php
namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class GetUserAddressService
{

    public function getAdress( $ip )
    {
        $client = new HttpClient();
       
        $responseObj = $client->create(['verify_peer' => false, 'verify_host' => false])->request('GET', 'http://api.ipstack.com/'.$ip.'?access_key=4ad0e02fbf2e1a55a886b65c9d4a7644');
        $responsJSON = $responseObj->getContent();
        $response = json_decode($responsJSON);
         
        return $response->location->geoname_id;
    }
}