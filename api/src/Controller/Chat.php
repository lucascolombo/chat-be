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

final class Chat
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getChats(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $params = $request->getQueryParams();
        $company = isset($params["company"]) && $params["company"] !== '' ? Encrypt::decode($params["company"]) : null;
        $limit = isset($params["limit"]) ? $params["limit"] : 10;
        $search = isset($params["search"]) ? $params["search"] : '';
        $nao_lido = isset($params["nao_lido"]) ? $params["nao_lido"] === "true" || $params["nao_lido"] === true  : false;
        $setor = isset($params["setor"]) ? $params["setor"] : '';
        $status = isset($params["status"]) ? $params["status"] : '';
        $tag = isset($params["tag"]) ? $params["tag"] : '';
        $user = isset($params["user"]) ? $params["user"] : '';

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->getChats($company, $limit, $search, $nao_lido, $setor, $status, $tag, $user, $userId));
    }

    public function getSingleChat(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $id = $route->getArgument('id');

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->getSingleChat($id, $userId));
    }

    public function getScheduleMessages(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $id = $route->getArgument('id');

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->getAllScheduledMessages($id, $userId));
    }

    public function sendScheduleMessage(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $id = $route->getArgument('id');

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->sendScheduleMessage($id, $userId));
    }

    public function deleteScheduleMessage(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $id = $route->getArgument('id');

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->deleteScheduleMessage($id, $userId));
    }

    public function newChat(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $body = $request->getParsedBody();
        $name = array_key_exists("name", $body) ? $body["name"] : "";
        $phone = array_key_exists("phone", $body) ? $body["phone"] : "";
        $country = array_key_exists("country", $body) ? $body["country"] : "";
        $setor = array_key_exists("setor", $body) ? $body["setor"] : "";
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->newChat($companyId, $name, $phone, $country, $setor, $userId));
    }
}
