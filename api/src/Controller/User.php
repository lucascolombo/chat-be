<?php
declare(strict_types=1);
namespace App\Controller;

use App\CustomResponse as Response;
use Pimple\Psr11\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repository\UserRepository;

final class User
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function changePassword(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();
        $password = array_key_exists("password", $body) ? $body["password"] : null;

        $userRepository = new UserRepository($this->container);
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($userRepository->changePassword($userId, $password));
    }

    public function recoverPassword(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();
        $email = array_key_exists("email", $body) ? $body["email"] : null;

        $userRepository = new UserRepository($this->container);

        return $response->withJson($userRepository->recoverPassword($email));
    }

    public function recoverUpdatePassword(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();
        $hash = array_key_exists("id", $body) ? $body["id"] : null;
        $password = array_key_exists("password", $body) ? $body["password"] : null;

        $userRepository = new UserRepository($this->container);

        return $response->withJson($userRepository->recoverUpdatePassword($hash, $password));
    }
}
