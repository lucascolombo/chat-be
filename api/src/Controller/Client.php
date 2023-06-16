<?php
declare(strict_types=1);
namespace App\Controller;

use App\CustomResponse as Response;
use Pimple\Psr11\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Slim\Routing\RouteContext;
use App\Lib\Encrypt;

final class Client
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function updateDisplayName(Request $request, Response $response, array $args): Response
    {
        $message = [ 'success' => false ];

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $id = $route->getArgument('id');
        $body = $request->getParsedBody();
        $name = array_key_exists("name", $body) ? $body["name"] : null;

        $clientRepository = new ClientRepository($this->container);

        return $response->withJson($clientRepository->updateDisplayName($id, $name));
    }

    public function start(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $userRepository = new UserRepository($this->container);
        $clientRepository = new ClientRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($clientRepository->start($id, $companyId, $userId));
    }
}
