<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return static function (App $app): void {
    $app->options('/{routes:.+}', function (Request $request, Response $response) {
        return $response;
    });

    $app->add(function (Request $request, $handler): Response {
        $response = $handler->handle($request);

        $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $response = $response->withHeader(
                'Access-Control-Allow-Headers',
                'X-Requested-With, Content-Type, Accept, Origin, Authorization'
        );
        $response = $response->withHeader(
                'Access-Control-Allow-Methods',
                'GET, POST, PUT, DELETE, PATCH, OPTIONS'
        );
        $response = $response->withHeader('Content-Type', 'application/json');

        return $response;
    });
};
