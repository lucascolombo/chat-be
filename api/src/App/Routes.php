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

$app->get('/', 'App\Controller\Home:home')->setName('home');
$app->post('/auth', 'App\Controller\Home:doLogin')->setName('login');
$app->get('/file/{id}/{filename}', 'App\Controller\File:getFile')->setName('getFile');
$app->get('/chats', 'App\Controller\Chat:getChats')->add(Auth::class)->setName('chats');
$app->get('/companies', 'App\Controller\Company:getUserCompanies')->add(Auth::class)->setName('companies');
$app->get('/filter/{id}', 'App\Controller\Company:getCompanyFiltersOptions')->add(Auth::class)->setName('filter');
$app->get('/messages/{id}/{companyId}', 'App\Controller\Messages:getMessages')->add(Auth::class)->setName('messages');
$app->post('/messages/{id}/tags', 'App\Controller\Messages:saveTags')->add(Auth::class)->setName('saveTags');
$app->post('/client/{id}', 'App\Controller\Client:updateDisplayName')->add(Auth::class)->setName('updateDisplayName');
$app->post('/messages/{id}/assign-user', 'App\Controller\Messages:assignUser')->add(Auth::class)->setName('assignUser');
$app->post('/messages/{id}/assign-setor', 'App\Controller\Messages:assignSetor')->add(Auth::class)->setName('assignSetor');
$app->post('/messages/{id}/add-to-queue', 'App\Controller\Messages:addToQueue')->add(Auth::class)->setName('addToQueue');
$app->post('/messages/{id}/remove-to-queue', 'App\Controller\Messages:removeToQueue')->add(Auth::class)->setName('removeToQueue');
$app->post('/client/{id}/start', 'App\Controller\Client:start')->add(Auth::class)->setName('start');
$app->post('/client/{id}/finish', 'App\Controller\Client:finish')->add(Auth::class)->setName('finish');
$app->post('/messages/{id}/transfer-setor', 'App\Controller\Messages:transferSetor')->add(Auth::class)->setName('transferSetor');
$app->post('/messages/{id}/transfer-user', 'App\Controller\Messages:transferUser')->add(Auth::class)->setName('transferUser');
$app->post('/messages/{id}/send-message', 'App\Controller\Messages:sendMessage')->add(Auth::class)->setName('sendMessage');
$app->post('/messages/{id}/read', 'App\Controller\Messages:read')->add(Auth::class)->setName('read');
$app->get('/company/{id}', 'App\Controller\Company:getCompanyData')->add(Auth::class)->setName('company');
$app->get('/schedule-messages/{id}', 'App\Controller\Chat:getScheduleMessages')->add(Auth::class)->setName('scheduleMessages');
$app->post('/send-schedule-message/{id}', 'App\Controller\Chat:sendScheduleMessage')->add(Auth::class)->setName('sendScheduleMessages');
$app->post('/delete-schedule-message/{id}', 'App\Controller\Chat:deleteScheduleMessage')->add(Auth::class)->setName('deleteScheduleMessages');

$app->setBasePath($path);
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
