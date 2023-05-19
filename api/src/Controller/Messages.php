<?php
declare(strict_types=1);
namespace App\Controller;

use App\CustomResponse as Response;
use Pimple\Psr11\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repository\ChatRepository;
use App\Repository\UserRepository;
use Slim\Routing\RouteContext;
use App\Lib\Encrypt;

final class Messages
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function saveTags(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $tags = array_key_exists("tags", $body) ? $body["tags"] : null;
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->saveTags($id, $tags, $companyId, $userId));
    }

    public function getMessages(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        
        $id = $route->getArgument('id');
        $companyId = Encrypt::decode($route->getArgument('companyId'));

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $params = $request->getQueryParams();
        $limit = isset($params["limit"]) ? $params["limit"] : 50;

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->getAllMessages($id, $limit, $userId, $companyId));
    }
}
