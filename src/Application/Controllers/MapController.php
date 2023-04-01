<?php
namespace App\Application\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Application\Settings\SettingsInterface;

class MapController {
    private $container;

    public function __construct($container)
    {
       $this->container = $container;
    }
    public function get_map(ServerRequestInterface $request, ResponseInterface $response){
        $data = json_encode(['data' => $this->container->get('db')->table('linky')->select('id')->get()]);
        $response->getBody()->write($data);

        return $response;
    }
}