<?php
declare(strict_types=1);
namespace App\Controller;

use App\CustomResponse as Response;
use Pimple\Psr11\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repository\ChatRepository;
use Slim\Routing\RouteContext;

final class Messages
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getMessages(Request $request, Response $response, array $args): Response
    {
        $message = [ 'success' => false ];

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $id = $route->getArgument('id');
        $params = $request->getQueryParams();
        $limit = isset($params["limit"]) ? $params["limit"] : 50;

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->getAllMessages($id, $limit));
    }
}
