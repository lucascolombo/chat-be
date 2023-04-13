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
        $search = isset($params["search"]) ? $params["search"] : '';
        $nao_lido = isset($params["nao_lido"]) ? $params["nao_lido"] === "true" || $params["nao_lido"] === true  : false;
        $setor = isset($params["setor"]) ? $params["setor"] : '';
        $status = isset($params["status"]) ? $params["status"] : '';
        $tag = isset($params["tag"]) ? $params["tag"] : '';
        $user = isset($params["user"]) ? $params["user"] : '';

        $chatRepository = new ChatRepository($this->container);

        return $response->withJson($chatRepository->getChats($limit, $search, $nao_lido, $setor, $status, $tag, $user));
    }
}
