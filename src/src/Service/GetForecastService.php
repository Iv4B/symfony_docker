<?php
namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class GetForecastService
{

    public function getForecast( $addr_id )
    {
        $client = new HttpClient();
        
        $response = $client->create(['verify_peer' => false, 'verify_host' => false])->request('GET', 'https://api.openweathermap.org/data/2.5/weather?id='.$addr_id.'&appid=26e29aa16ee3a3a8af761f4dd0410824');

        return $response->getContent();
    }
}