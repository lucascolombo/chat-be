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
        $company = $user->getCompanyId();

        $params = $request->getQueryParams();
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

    public function updateChatStatus(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $chatId = $route->getArgument('id');

        $body = $request->getParsedBody();
        $status = array_key_exists("status", $body) ? $body["status"] : "1";
        $abort = array_key_exists("abort", $body) ? $body["abort"] : "1";
        $statusLabel = array_key_exists("statusLabel", $body) ? $body["statusLabel"] : ( $status == 1 ? "" : "Adiado" );
        $statusDateValidity = array_key_exists("statusDateValidity", $body) ? $body["statusDateValidity"] : ($status == 1 ? "0" : time());
        $endMessage = array_key_exists("endMessage", $body) ? $body["endMessage"] : "";
        $telefone = array_key_exists("telefone", $body) ? $body["telefone"] : "0";
        $companyId = $user->getCompanyId();
        $deviceId = array_key_exists("deviceId", $body) ? $body["deviceId"] : "0";

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->updateChatStatus($chatId, $userId, $status, $abort, $statusLabel, $statusDateValidity, $endMessage, $telefone, $companyId, $deviceId));
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
        $device_id = array_key_exists("device_id", $body) ? $body["device_id"] : "";
        $companyId = $user->getCompanyId();

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->newChat($companyId, $name, $phone, $country, $setor, $userId, $device_id));
    }

    public function markAsRead(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $id = $route->getArgument('id');

        $body = $request->getParsedBody();
        $companyId = $user->getCompanyId();

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->markAsRead($id, $companyId, $userId));
    }

    public function markAsUnread(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $id = $route->getArgument('id');

        $body = $request->getParsedBody();
        $companyId = $user->getCompanyId();

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->markAsUnread($id, $companyId, $userId));
    }
}
