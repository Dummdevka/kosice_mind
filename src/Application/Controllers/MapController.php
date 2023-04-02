<?php
namespace App\Application\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Application\Settings\SettingsInterface;
use GuzzleHttp\Client;

class MapController {
    private $container;
    private $client;

    public function __construct($container)
    {
       $this->container = $container;

       $this->client = new Client();
    }

    public function main($request, $response) {
        $type = $request->getParsedBody()['type'];
        $final = [];

        $cubes = $request->getParsedBody()['cubes']; 
        $function = 'get_' . $type;
        if($type != 'stores')$data = $this->$function();
        
        foreach($cubes as $cube) {
            $tmp = [];
            // $x = (float)$request->getParsedBody()['x'];
            // $y = (float)$request->getParsedBody()['y'];
            $x = $cube['x'];
            $y = $cube['y'];
    
    
            
    
            if($type != 'stores') $result = $this->find_nearest($data, $x, $y);
            else $result = $this->get_stores($x, $y); 
            $tmp['id'] = $cube['id'];
            $tmp['data'] = $result;
            $final[] = $tmp;
        }

        if($type != 'stores')$response->getBody()->write(json_encode($final));
        else $response->getBody()->write(json_encode($final)); 

        return $response;
    }

    public function get_data($url, $key) {
        $data = $this->client->get($url);
        $schools = json_decode($data->getBody()->getContents(), true);
        $result = [];
        foreach($schools['features'] as $school) {
            $tmp = [];
            $tmp['name'] = $school['attributes'][$key];
            $tmp['x'] = $school['attributes']['x'];
            $tmp['y'] = $school['attributes']['y'];
            $result[] = $tmp;
        }
        return $result;
    }

    public function get_schools() {
        $url = "https://services-eu1.arcgis.com/qrtO0RIRViAdEN4F/arcgis/rest/services/stredne_skoly/FeatureServer/0/query?where=1%3D1&outFields=*&outSR=4326&f=json";

        $result = $this->get_data($url, 'organizacia_nazov');

        return $result;
    }

    public function get_houses() {
        $data = $this->container->get('db')->table('budovy')->get();

        $result = [];
        foreach($data as $house) {
            $tmp = [];
            $tmp['x'] = $house->x;
            $tmp['y'] = $house->y;
            $tmp['ulica_house'] = $house->ulica_house;
            $tmp['house_type'] = $house->house_type;
            $result[] = $tmp;
        }
        return $result;
    }

    public function get_posts() {
        $data = $this->container->get('db')->table('posta')->get();

        $result = [];
        foreach($data as $posta) {
            $tmp = [];
            $tmp['x'] = $posta->x;
            $tmp['y'] = $posta->y;
            $tmp['name'] = $posta->organizacia_nazov;
            $result[] = $tmp;
        }
        return $result;
    }

    public function get_busstops() {
        $url = "https://services-eu1.arcgis.com/qrtO0RIRViAdEN4F/arcgis/rest/services/zastavky_mhd/FeatureServer/0/query?where=1%3D1&outFields=*&outSR=4326&f=json";

        $result = $this->get_data($url, 'zastavka_nazov');
        // $response->getBody()->write(json_encode($result));

        return $result;
    }

    public function get_medicals() {
        $data = $this->container->get('db')->table('medicals')->get();

        $result = [];
        foreach($data as $medical) {
            $tmp = [];
            $coordinates = explode(',', $medical->geometry);
            $tmp['x'] = $coordinates[1];
            $tmp['y'] = $coordinates[0];
            $result[] = $tmp;
        }
        return $result;

    }


    // public function get_galleries() {
    //     $data = $this->container->get('db')->table('galleries')->get();

    //     $result = [];
    //     foreach($data as $gallery) {
    //         $tmp = [];
    //         $coordinates = explode(' ', $gallery->geometry);
    //         $tmp['x'] = $coordinates[1];
    //         $tmp['y'] = $coordinates[0];
    //         $result[] = $tmp;
    //     }
    //     return $result;
    // }

    public function get_map(ServerRequestInterface $request, ResponseInterface $response){
        $data = json_encode(['data' => $this->container->get('db')->table('linky')->select('id')->get()]);
        $response->getBody()->write($data);

        return $response;
    }

    public function find_nearest($data, $x, $y) {
        $result = $data[0];
        $origin = [
            'x' => $x,
            'y' => $y
        ];
        foreach($data as $org) {
            

            $org_distance = $this->distance($origin, $org);
            $result_distance = $this->distance($origin, $result);

            if($org_distance < $result_distance) $result = $org;
        }
        return [
            'result' => $result,
            'distance' => $result_distance
        ];
    }

    function distance($a, $b)
    {
        $delta_lat = $b['y'] - $a['y'] ;
        $delta_lon = $b['x'] - $a['x'] ;

        $earth_radius = 6372.795477598;

        $alpha    = $delta_lat/2;
        $beta     = $delta_lon/2;
        $a        = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($a['y'])) * cos(deg2rad($b['y'])) * sin(deg2rad($beta)) * sin(deg2rad($beta)) ;
        $c        = asin(min(1, sqrt($a)));
        $distance = 2*$earth_radius * $c;
        $distance = round($distance, 4);
        return $distance;
    }

    // public function use_api($category){}

    public function get_stores($x, $y) {
        $category = "600-6000-0061";

        $url = "https://browse.search.hereapi.com/v1/browse?at=" . $y . "," . $x ."&limit=1&categories=" . $category . "&apiKey=" . $this->container->get('api_key');
        $headers = [
            'Authorization' => 'Bearer ' . $this->container->get('api_access_token')
        ];
        $data = json_decode($this->client->get($url)->getBody()->getContents(), true);

        return $data['items'][0];

    }
}