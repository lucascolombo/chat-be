<?php
declare(strict_types=1);
namespace App\Controller;

use App\CustomResponse as Response;
use Pimple\Psr11\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteContext;
use Slim\Http\RequestBody;
use GuzzleHttp\Psr7\LazyOpenStream;

final class File
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getFile(Request $request, Response $response)
    {
        $message = [ 'success' => true ];

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $id = $route->getArgument('id');
        $filename = $route->getArgument('filename');

        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $id . '/' . $filename;

        if (file_exists($filePath)) {
            $stream = new LazyOpenStream($filePath, 'r');
            return $response->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Disposition', 'attachment;filename="' . basename($filePath) . '"')
                ->withHeader('Content-Transfer-Encoding', 'binary')
                ->withHeader('Expires', '0')
                ->withHeader('Cache-Control', 'must-revalidate')
                ->withHeader('Pragma', 'public')
                ->withHeader('Content-Length', filesize($filePath))
                ->withBody($stream);
        }
        else {
            return $response->withJson([ 'success' => false ]);
        }
    }
}
