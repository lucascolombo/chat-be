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
    $userRepository = new UserRepository($this->container);                                                                                
    $user = $userRepository->getUserByHeaders($request);

    if ($user === null) {
        throw new \Exception("token invalid");
    }

    $response = $handler->handle($request);  
  
    return $response;
  }
}

$app->get('/', 'App\Controller\Home:home')->setName('home');
$app->post('/auth', 'App\Controller\Home:doLogin')->setName('login');
$app->post('/auth-company', 'App\Controller\Home:loginWithCompany')->setName('loginWithCompany');
$app->get('/file/{id}/{filename}', 'App\Controller\File:getFile')->setName('getFile');
$app->get('/chats', 'App\Controller\Chat:getChats')->add(Auth::class)->setName('chats');
$app->get('/chat/{id}', 'App\Controller\Chat:getSingleChat')->add(Auth::class)->setName('getSingleChat');
$app->post('/chat/{id}/status', 'App\Controller\Chat:updateChatStatus')->add(Auth::class)->setName('updateChatStatus');
$app->get('/companies', 'App\Controller\Company:getUserCompanies')->add(Auth::class)->setName('companies');
$app->get('/filter', 'App\Controller\Company:getCompanyFiltersOptions')->add(Auth::class)->setName('filter');
$app->get('/messages/{id}', 'App\Controller\Messages:getMessages')->add(Auth::class)->setName('messages');
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
$app->post('/messages/{id}/upload-files', 'App\Controller\Messages:uploadFiles')->add(Auth::class)->setName('uploadFiles');
$app->post('/messages/{id}/send-whatsapp', 'App\Controller\Messages:sendMessageWhatsapp')->add(Auth::class)->setName('sendMessageWhatsapp');
$app->post('/messages/{id}/read', 'App\Controller\Messages:read')->add(Auth::class)->setName('read');
$app->post('/messages/{id}/open', 'App\Controller\Messages:open')->add(Auth::class)->setName('open');
$app->get('/company', 'App\Controller\Company:getCompanyData')->add(Auth::class)->setName('company');
$app->post('/company/upload-file', 'App\Controller\Company:uploadFile')->add(Auth::class)->setName('companyUploadFile');
$app->post('/company/create-file', 'App\Controller\Company:createFile')->add(Auth::class)->setName('companyCreateFile');
$app->post('/company/delete-file/{fileId}', 'App\Controller\Company:deleteFile')->add(Auth::class)->setName('companyDeleteFile');
$app->get('/company/fixed-files', 'App\Controller\Company:getAllFixedFiles')->add(Auth::class)->setName('companyGetAllFixedFiles');
$app->get('/schedule-messages/{id}', 'App\Controller\Chat:getScheduleMessages')->add(Auth::class)->setName('scheduleMessages');
$app->post('/send-schedule-message/{id}', 'App\Controller\Chat:sendScheduleMessage')->add(Auth::class)->setName('sendScheduleMessages');
$app->post('/delete-schedule-message/{id}', 'App\Controller\Chat:deleteScheduleMessage')->add(Auth::class)->setName('deleteScheduleMessages');
$app->post('/message/new', 'App\Controller\Chat:newChat')->add(Auth::class)->setName('newChat');
$app->post('/chat/{id}/markAsRead', 'App\Controller\Chat:markAsRead')->add(Auth::class)->setName('markAsRead');
$app->post('/chat/{id}/markAsUnread', 'App\Controller\Chat:markAsUnread')->add(Auth::class)->setName('markAsUnread');
$app->post('/company/create-default-message', 'App\Controller\Company:createDefaultMessage')->add(Auth::class)->setName('createDefaultMessage');
$app->get('/company/default-messages', 'App\Controller\Company:getAllDefaultMessages')->add(Auth::class)->setName('getAllDefaultMessages');
$app->post('/company/default-messages/{idMessage}', 'App\Controller\Company:updateMessage')->add(Auth::class)->setName('updateMessage');
$app->post('/company/share-default-messages/', 'App\Controller\Company:shareMessages')->add(Auth::class)->setName('shareMessages');
$app->get('/company/all-users/', 'App\Controller\Company:getAllCompanyUsers')->add(Auth::class)->setName('getAllCompanyUsers');
$app->post('/company/delete-message/{idMessage}', 'App\Controller\Company:deleteMessage')->add(Auth::class)->setName('deleteMessage');
$app->post('/company/reorder-message/{idMessage}', 'App\Controller\Company:reorderMessage')->add(Auth::class)->setName('reorderMessage');
$app->post('/company/add-user', 'App\Controller\Company:addUser')->add(Auth::class)->setName('addUser');
$app->post('/company/edit-user', 'App\Controller\Company:editUser')->add(Auth::class)->setName('editUser');
$app->get('/company/get-users/', 'App\Controller\Company:getEmployees')->add(Auth::class)->setName('getEmployees');
$app->get('/company/get-all-company-departments/', 'App\Controller\Company:getAllCompanyDepartments')->add(Auth::class)->setName('getAllCompanyDepartments');
$app->get('/company/access-time/{employeeId}', 'App\Controller\Company:getUserAccessTime')->add(Auth::class)->setName('getUserAccessTime');
$app->post('/user/update-password', 'App\Controller\User:changePassword')->add(Auth::class)->setName('changePassword');
$app->post('/recover-password', 'App\Controller\User:recoverPassword')->setName('recoverPassword');
$app->post('/recover-update-password', 'App\Controller\User:recoverUpdatePassword')->setName('recoverUpdatePassword');
$app->post('/company/save-ura', 'App\Controller\Company:saveURA')->add(Auth::class)->setName('saveURA');
$app->get('/company/get-ura/{device_id}', 'App\Controller\Company:getURA')->add(Auth::class)->setName('getURA');
$app->get('/company/get-devices', 'App\Controller\Company:getAllDevices')->add(Auth::class)->setName('getAllDevices');
$app->post('/company/department', 'App\Controller\Company:updateDepartment')->add(Auth::class)->setName('updateDepartment');
$app->post('/company/delete-department/{department_id}', 'App\Controller\Company:deleteDepartment')->add(Auth::class)->setName('deleteDepartment');
$app->get('/company/tags', 'App\Controller\Company:getAllCompanyTags')->add(Auth::class)->setName('getAllCompanyTags');
$app->post('/company/tags', 'App\Controller\Company:saveCompanyTags')->add(Auth::class)->setName('saveCompanyTags');
$app->post('/company/delete-tag-group', 'App\Controller\Company:deleteTagGroup')->add(Auth::class)->setName('deleteTagGroup');
$app->post('/company/delete-tag', 'App\Controller\Company:deleteTag')->add(Auth::class)->setName('deleteTag');
$app->get('/company/department-access-time/{departmentId}', 'App\Controller\Company:getDepartmentAccessTime')->add(Auth::class)->setName('getDepartmentAccessTime');
$app->post('/audio/transcript/{message_id}', 'App\Controller\Messages:transcript')->add(Auth::class)->setName('transcript');
$app->get('/device/disconnect/{deviceId}', 'App\Controller\Company:disconnectDevice')->add(Auth::class)->setName('disconnectDevice');
$app->post('/device/generate-qr-code/{deviceId}', 'App\Controller\Company:generateQRCode')->add(Auth::class)->setName('generateQRCode');
$app->post('/company/graphs', 'App\Controller\Company:getGraphs')->add(Auth::class)->setName('getGraphs');
$app->post('/company/ignore-chat', 'App\Controller\Company:ignoreChat')->add(Auth::class)->setName('ignoreChat');

$app->setBasePath($path);
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
