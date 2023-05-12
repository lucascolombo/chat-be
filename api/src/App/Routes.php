<?php

declare(strict_types=1);

use Slim\Routing\RouteContext;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Repository\UserRepository;

$path = $_SERVER['SLIM_BASE_PATH'] ?? '';

class Auth {
  private $container;

  public function __construct($container) {
      $this->container = $container;
  }

  public function __invoke(Request $request, RequestHandler $handler) {
    $response = $handler->handle($request);    
    $userRepository = new UserRepository($this->container);                                                                                
    $user = $userRepository->getUserByHeaders($request);

    if ($user === null) {
        throw new \Exception("token invalid");
    }
  
    return $response;
  }
}

$app->post('/', 'App\Controller\Home:home')->setName('home');
$app->post('/auth', 'App\Controller\Home:doLogin')->setName('login');
$app->get('/chats', 'App\Controller\Chat:getChats')->add(Auth::class)->setName('chats');
$app->get('/companies', 'App\Controller\Company:getUserCompanies')->add(Auth::class)->setName('companies');
$app->get('/filter/{id}', 'App\Controller\Company:getCompanyFiltersOptions')->add(Auth::class)->setName('filter');
$app->get('/messages/{id}/{companyId}', 'App\Controller\Messages:getMessages')->add(Auth::class)->setName('messages');
$app->post('/client/{id}', 'App\Controller\Client:updateDisplayName')->add(Auth::class)->setName('updateDisplayName');

$app->setBasePath($path);
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
