<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Repository\UserRepository;

return static function (App $app, Closure $customErrorHandler): void {
    $path = $_SERVER['SLIM_BASE_PATH'] ?? '';

    class Auth {
        private $container;

        public function __construct($container) {
            $this->container = $container;
        }

        public function __invoke(Request $request, RequestHandler $handler) {
            $isValidRequest = false;
        
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();
        
            $publicRoutes = ['login'];
        
            if (empty($route)) {
                header('HTTP/1.0 400 Bad Request');
                exit;
            }
        
            $routeName = $route->getName();
        
            if (!in_array($routeName, $publicRoutes)) {
                $server = $request->getServerParams();
        
                if (! preg_match('/Bearer\s(\S+)/', $server['HTTP_AUTHORIZATION'], $matches)) {
                    header('HTTP/1.0 400 Bad Request');
                    exit;
                }
        
                $jwt = $matches[1];
        
                if (!$jwt) {
                    header('HTTP/1.0 400 Bad Request');
                    exit;
                }
        
                $token = JWT::decode($jwt, new Key($_SERVER['JWT_SECRET_KEY'], 'HS512'));
                $now = new \DateTimeImmutable();
                $server = $request->getServerParams();
        
                if ($token->iss !== $server['HTTP_HOST'] || $token->nbf > $now->getTimestamp() || $token->exp < $now->getTimestamp()) {
                    header('HTTP/1.1 401 Unauthorized');
                    exit;
                }

                $logged_in = $token->iat;
                $email = $token->userName;

                $userRepository = new UserRepository($this->container);
                $user = $userRepository->getUserByEmail($email);
    
                if ($user->getLoggedIn() != $logged_in) {
                    header('HTTP/1.1 401 Unauthorized');
                    exit;
                }
            }
        
            $response = $handler->handle($request);
        
            return $response;
        }
    }
    
    $app->add('Auth');

    $app->setBasePath($path);
    $app->addRoutingMiddleware();
    $app->addBodyParsingMiddleware();
    $displayError = filter_var(
        $_SERVER['DISPLAY_ERROR_DETAILS'] ?? false,
        FILTER_VALIDATE_BOOLEAN
    );
    $errorMiddleware = $app->addErrorMiddleware($displayError, true, true);
    $errorMiddleware->setDefaultErrorHandler($customErrorHandler);
};