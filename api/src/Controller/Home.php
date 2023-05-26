<?php
declare(strict_types=1);
namespace App\Controller;

use App\CustomResponse as Response;
use Pimple\Psr11\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\Repository\UserRepository;

final class Home
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function home(Request $request, Response $response): Response
    {
        $message = [ 'success' => true ];

        return $response->withJson($message);
    }

    public function doLogin(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];
        $body = $request->getParsedBody();

        $email = array_key_exists("email", $body) ? $body["email"] : null;
        $password = array_key_exists("password", $body) ? $body["password"] : null;

        if ($email && $password) {
            $userRepository = new UserRepository($this->container);
            $user = $userRepository->getUserByEmail($email);

            if ($user && password_verify($password, $user->getPassword())) {
                $issuedAt = new \DateTimeImmutable();
                $jwt = $this->createJWT($request, $email, $issuedAt);

                $message['success'] = $userRepository->updateUserLoggedIn($user, $issuedAt);
                $message['jwt'] = $jwt;
            }
            else {
                $message['error'] = 'USER_LOGIN_ERROR';
            }
        }
        else {
            $message['error'] = 'DATA_NOT_FOUND_ERROR';
        }

        return $response->withJson($message);
    }

    private function createJWT(Request $request, String $username, \DateTimeImmutable $issuedAt) {
        $expire = $issuedAt->modify('+24 hours')->getTimestamp();
        $serverName = $request->getServerParams()['HTTP_HOST'];                                   

        $data = [
            'iat'  => $issuedAt->getTimestamp(),
            'iss'  => $serverName,
            'nbf'  => $issuedAt->getTimestamp(),
            'exp'  => $expire,
            'userName' => $username,
        ];

        return JWT::encode(
            $data,
            $_SERVER['JWT_SECRET_KEY'],
            'HS512'
        );
    }
}
