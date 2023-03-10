<?php

declare(strict_types=1);

use Slim\Routing\RouteContext;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Repository\UserRepository;

$path = $_SERVER['SLIM_BASE_PATH'] ?? '';

class Auth {
  private $container;

  public function __construct($container) {
      $this->container = $container;
  }

  public function __invoke(Request $request, RequestHandler $handler) {
    $response = $handler->handle($request);                                                                                    
    $headers = $request->getHeaders();

    if (!preg_match('/Bearer\s(\S+)/', $headers['Authorization'][0], $matches)) {
      throw new \Exception("token not found");
    }

    $jwt = $matches[1];

    if (!$jwt) {
        throw new \Exception("Token not found");
    }

    $token = JWT::decode($jwt, new Key($_SERVER['JWT_SECRET_KEY'], 'HS512'));
    $now = new \DateTimeImmutable();
    $server = $request->getServerParams();

    if ($token->iss !== $server['HTTP_HOST'] || $token->nbf > $now->getTimestamp() || $token->exp < $now->getTimestamp()) {
        throw new \Exception("token invalid");
    }

    $logged_in = $token->iat;
    $email = $token->userName;

    $userRepository = new UserRepository($this->container);
    $user = $userRepository->getUserByEmail($email);

    if ($user->getLoggedIn() != $logged_in) {
        throw new \Exception("token invalid");
    }
  
    return $response;
  }
}

$app->post('/', 'App\Controller\Home:home')->setName('home');
$app->post('/auth', 'App\Controller\Home:doLogin')->setName('login');
$app->get('/chats', 'App\Controller\Chat:getChats')->add(Auth::class)->setName('chats');
$app->get('/messages/{id}', 'App\Controller\Messages:getMessages')->add(Auth::class)->setName('messages');

$app->setBasePath($path);
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
