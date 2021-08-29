<?php
namespace App\Controller;

use App\Service\GetIpAdressService;
use App\Service\GetUserAddressService;
use App\Service\GetForecastService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use App\Entity\Forecast;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function home(GetIpAdressService $GetIpAdressService): Response
    {
        $ip = $GetIpAdressService->getIp();

        $forecast = $this->getCache( $ip );  

        return new Response(
            '<html>
                <body>'
                    .$forecast.  
                '</body>
            </html>'
        );
    }

    /*
        /clear iztīra cached vērtību
    */
    public function clear(): Response
    {
        $cache = new FilesystemAdapter();
        $cache->deleteItem('stats.forecast');


        return new Response(
            '<html>
                <body>
                    Cache cleared
                </body>
            </html>'
        );
    }

    /*
        /force piespiedu datu update
    */
    public function force(GetIpAdressService $GetIpAdressService): Response
    {
        $ip = $GetIpAdressService->getIp();
        $this->getDbData($ip, true);


        return new Response(
            '<html>
                <body>
                    Force update
                </body>
            </html>'
        );
    }

    /*
     Atgriež cache vērtību ja cached ip sakrīt, ja ne tad pārbauda db ierakstus un iestata jaunus cache datus
    */
    private function getCache( $ip )
    {
        $cache = new FilesystemAdapter();
        $forecastCache = $cache->getItem('stats.forecast');
        
        if($forecastCache->isHit()):
            $cahedData = $forecastCache->get();
            $data = unserialize($cahedData);
            if( $data['ip'] == $ip ):
                return $data['forecast'];
            else:
                $forecastInfo = $this->getDbData( $ip );
                $forecast = $this->setCache($ip, $cache, $forecastCache, $forecastInfo );

                return $forecast;
            endif;            
        else:

            $data = $this->getDbData( $ip );

            $this->setCache($ip, $cache, $forecastCache, $data);
            return $data;  
        endif;   
        
        return false;
    }

    /*
        Atgriež db ierakstus, ja eksistē. 
        Ja atgrieztie dati ir vecāki par 5min, tad pieprasa jaunus un updeito ierakstus.

        Ja ieraksts neeksistē, tad tiek pieprasīti jauni dati un saglabāti DB.
    */
    private function getDbData( $ip, $force = false )
    {
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $entityManager->getRepository(Forecast::class); 
        $dbdata = $repository->findOneByIP($ip);
        $entityManager->flush();
        
        $data = false;
        if( $dbdata ):
            $data = $dbdata->getForecast();

            $time = $dbdata->getUpdated();
            
            if( time() - $time > 300 || $force ):
                
                $data = $this->getAPIResults( $ip );
                $dbdata->setForecast($data);
                $dbdata->setUpdated(time());
                $entityManager->flush();
            endif;    
        else:
            $data = $this->getAPIResults( $ip );
            $this->createForecast($ip, $data, time());
        endif;  

        return $data;
    }

    /*
        Iegūst api datus
    */
    private function getAPIResults($ip)
    {
        $GetUserAddressService = new GetUserAddressService();
        $GetForecastService = new GetForecastService();
        $address = $GetUserAddressService->getAdress( $ip );
        $forecast = $GetForecastService->getForecast( $address );

        return $forecast;
    }


    /*
        Iestata Cache
    */
    private function setCache($ip, $cache, $forecastCache, $data)
    {
        $GetUserAddressService = new GetUserAddressService();
        $GetForecastService = new GetForecastService();
        $address = $GetUserAddressService->getAdress( $ip );
        $forecast = $GetForecastService->getForecast( $address );

        $cacheArray = array(
            'ip' => $ip,
            'forecast' => $data
        );

        $forecastCache->set(serialize($cacheArray));
        $cache->save($forecastCache); 
    }

    /*
        Izveido jaunu db ierakstu
    */
    public function createForecast($ip, $data, $updated)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $forecast = new Forecast();
        $forecast->setIp($ip);
        $forecast->setForecast($data);
        $forecast->setUpdated($updated);

        $entityManager->persist($forecast);

        
        $entityManager->flush();

        return 'Saved new forecast with id '.$forecast->getId();
    }
}