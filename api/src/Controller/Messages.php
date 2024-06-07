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

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

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

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->assignUser($id, $assignedUserId, $assignedDate, $companyId, $userId));
    }

    public function addToQueue(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->addToQueue($id, $userId, $companyId));
    }

    public function removeToQueue(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->removeToQueue($id, $userId, $companyId));
    }

    public function assignSetor(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $assignedSetorId = array_key_exists("setor", $body) ? $body["setor"] : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->assignSetor($id, $assignedSetorId, $companyId, $userId));
    }

    public function getMessages(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        
        $id = $route->getArgument('id');

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

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

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->transferSetor($id, $setor, $companyId, $userId));
    }

    public function transferUser(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');
        $userTransfer = array_key_exists("user", $body) ? $body["user"] : null;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->transferUser($id, $userTransfer, $companyId, $userId));
    }

    public function sendMessage(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');

        $text = array_key_exists("text", $body) ? $body["text"] : "";
        $scheduleDate = array_key_exists("scheduleDate", $body) ? $body["scheduleDate"] : 0;
        $message_type = array_key_exists("messageType", $body) ? $body["messageType"] : 0;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->sendMessage($id, $userId, $companyId, $text, $scheduleDate, $message_type));
    }

    public function uploadFiles(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        $files = $request->getUploadedFiles();
        
        $id = $route->getArgument('id');

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->uploadFiles($id, $userId, $companyId, $files));
    }

    public function sendMessageWhatsapp(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');

        $text = array_key_exists("text", $body) ? $body["text"] : "";
        $messageId = array_key_exists("messageId", $body) ? $body["messageId"] : 0;
        $media = array_key_exists("media", $body) ? $body["media"] : 0;

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->sendMessageWhatsapp($messageId, $id, $userId, $companyId, $text, $media));
    }

    public function read(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->read($id, $userId, $companyId));
    }

    public function open(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();
        
        $id = $route->getArgument('id');

        $userRepository = new UserRepository($this->container);
        $chatRepository = new ChatRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($chatRepository->open($id, $userId, $companyId));
    }
}
