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

    public function assignUser(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $assignedUserId = array_key_exists("user", $body) ? $body["user"] : null;
        $assignedDate = array_key_exists("date", $body) ? $body["date"] : null;
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->assignUser($id, $assignedUserId, $assignedDate, $companyId, $userId));
    }

    public function addToQueue(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->addToQueue($id, $userId, $companyId));
    }

    public function removeToQueue(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->removeToQueue($id, $userId, $companyId));
    }

    public function assignSetor(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $assignedSetorId = array_key_exists("setor", $body) ? $body["setor"] : null;
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->assignSetor($id, $assignedSetorId, $companyId, $userId));
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

    public function transferSetor(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $setor = array_key_exists("setor", $body) ? $body["setor"] : null;
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->transferSetor($id, $setor, $companyId, $userId));
    }

    public function transferUser(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $userTransfer = array_key_exists("user", $body) ? $body["user"] : null;
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->transferUser($id, $userTransfer, $companyId, $userId));
    }

    public function sendMessage(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        $files = $request->getUploadedFiles();
        
        $id = $route->getArgument('id');

        $text = array_key_exists("text", $body) ? $body["text"] : "";
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;
        $scheduleDate = array_key_exists("scheduleDate", $body) ? $body["scheduleDate"] : 0;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->sendMessage($id, $userId, $companyId, $text, $scheduleDate, $files));
    }

    public function sendMessageWhatsapp(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');

        $text = array_key_exists("text", $body) ? $body["text"] : "";
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;
        $messageId = array_key_exists("messageId", $body) ? $body["messageId"] : 0;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->sendMessageWhatsapp($messageId, $id, $userId, $companyId, $text));
    }

    public function read(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->read($id, $userId, $companyId));
    }

    public function open(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $companyId = array_key_exists("companyId", $body) ? Encrypt::decode($body["companyId"]) : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($chatRepository->open($id, $userId, $companyId));
    }
}
