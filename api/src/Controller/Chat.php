<?php
declare(strict_types=1);
namespace App\Controller;

use App\CustomResponse as Response;
use Pimple\Psr11\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repository\ChatRepository;

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

        $params = $request->getQueryParams();
        $limit = isset($params["limit"]) ? $params["limit"] : 10;

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->getChats($limit));
    }
}
