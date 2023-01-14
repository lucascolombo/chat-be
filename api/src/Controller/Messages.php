<?php
declare(strict_types=1);
namespace App\Controller;

use App\CustomResponse as Response;
use Pimple\Psr11\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class Messages
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getMessages(Request $request, Response $response): Response
    {
        $message = [ 'success' => true ];

        return $response->withJson($message);
    }
}
