<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Views\PhpRenderer;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/map', function (Request $request, Response $response) use($app) {
        $view = __DIR__ . '/../public/view/index.html';
        $this->get('renderer')->render($response,'/index.html');
        return $response;
    });

    $app->get('/adminpanel.html', function (Request $request, Response $response) use($app) {
        $view = __DIR__ . '/../public/view/index.html';
        $this->get('renderer')->render($response,'/admin.html');
        return $response;
    });

    $app->get('/assets/{filename}', function (Request $request, Response $response, $args) {
        $file_content = file_get_contents('../public/view/assets/' . $args['filename']);
        // $this->get('renderer')->render($response,'/index.html');
        $response->getBody()->write($file_content);
        $ext = last(explode('.', $args['filename']));
        $header = $ext == 'css' ? 'css' : 'javascript';
        return $response->withHeader('Content-Type', 'text/' . $header);
    });
    $app->get('/', \MapController::class . ':get_busstops');
    $app->post('/main', \MapController::class . ':main');

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });
};
