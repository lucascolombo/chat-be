<?php
declare(strict_types=1);
namespace App\Controller;

use App\CustomResponse as Response;
use Pimple\Psr11\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Repository\CompanyRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Repository\UserRepository;
use App\Lib\User;
use App\Lib\Encrypt;
use Slim\Routing\RouteContext;

final class Company
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getCompanyFiltersOptions(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];
        $companyRepository = new CompanyRepository($this->container);

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getCompanyFiltersOptions($userId, $companyId));
    }

    public function getUserCompanies(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];
        $companyRepository = new CompanyRepository($this->container);

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($companyRepository->getUserCompanies($userId));
    }

    public function getCompanyData(Request $request, Response $response): Response
    {
        $message = [ 'success' => false ];
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $id = Encrypt::decode($route->getArgument('id'));

        $companyRepository = new CompanyRepository($this->container);

        $userRepository = new UserRepository($this->container);                                                                                
        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($companyRepository->getCompanyData($userId, $id));
    }

    public function uploadFile(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $files = $request->getUploadedFiles();

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->uploadFile($userId, $companyId, $files));
    }

    public function deleteFile(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $body = $request->getParsedBody();
        
        $fileId = $route->getArgument('fileId');

        $deleteAll = array_key_exists("deleteAll", $body) ? $body["deleteAll"] : 0;

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->deleteFile($userId, $companyId, $fileId, $deleteAll));
    }

    public function createFile(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $file = array_key_exists("file", $body) ? $body["file"] : "";
        $label = array_key_exists("label", $body) ? $body["label"] : "";
        $setor = array_key_exists("setor", $body) ? $body["setor"] : [];
        $tag = array_key_exists("tag", $body) ? $body["tag"] : 0;
        $where = array_key_exists("where", $body) ? $body["where"] : 0;
        $fileSize = array_key_exists("fileSize", $body) ? $body["fileSize"] : 0;
        $fileType = array_key_exists("fileType", $body) ? $body["fileType"] : 0;

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->createFile($userId, $companyId, $file, $label, $setor, $tag, $where, $fileSize, $fileType));
    }

    public function getAllFixedFiles(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getAllFixedFiles($userId, $companyId));
    }

    public function createDefaultMessage(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $title = array_key_exists("title", $body) ? $body["title"] : "";
        $message = array_key_exists("message", $body) ? $body["message"] : "";
        $tag = array_key_exists("tag", $body) ? $body["tag"] : 0;

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->createDefaultMessage($userId, $companyId, $title, $message, $tag));
    }

    public function getAllDefaultMessages(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getAllDefaultMessages($userId, $companyId));
    }

    public function updateMessage(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $messageId = $route->getArgument('idMessage');

        $title = array_key_exists("title", $body) && $body["title"] !== "" ? $body["title"] : null;
        $content = array_key_exists("content", $body) && $body["content"] !== "" ? $body["content"] : null;

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->updateMessage($userId, $companyId, $messageId, $title, $content));
    }

    public function shareMessages(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $users = array_key_exists("users", $body) ? $body["users"] : [];
        $messages = array_key_exists("messages", $body) ? $body["messages"] : [];

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->shareMessages($userId, $companyId, $users, $messages));
    }

    public function getAllCompanyUsers(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getAllCompanyUsers($userId, $companyId));
    }

    public function deleteMessage(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $messageId = $route->getArgument('idMessage');

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->deleteMessage($userId, $companyId, $messageId));
    }

    public function reorderMessage(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $messageId = $route->getArgument('idMessage');

        $order = array_key_exists("order", $body) && $body["order"] !== "" ? $body["order"] : 0;

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->reorderMessage($userId, $companyId, $messageId, $order));
    }

    public function addUser(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $name = array_key_exists("name", $body) && $body["name"] !== "" ? $body["name"] : '';
        $email = array_key_exists("email", $body) && $body["email"] !== "" ? $body["email"] : '';
        $phone = array_key_exists("phone", $body) && $body["phone"] !== "" ? $body["phone"] : '';
        $password = array_key_exists("password", $body) && $body["password"] !== "" ? $body["password"] : '';

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->addUser($userId, $companyId, $name, $email, $phone, $password));
    }

    public function editUser(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $editUserId = array_key_exists("id", $body) && $body["id"] !== "" ? $body["id"] : 0;
        $departments = array_key_exists("departments", $body) ? $body["departments"] : [];
        $displayName = array_key_exists("display_name", $body) && trim($body["display_name"]) !== "" ? $body["display_name"] : "";

        $activate_access = array_key_exists("activate_access", $body) && $body["activate_access"] ? $body["activate_access"] : false;
        $grammar_correction = array_key_exists("grammar_correction", $body) && $body["grammar_correction"] ? $body["grammar_correction"] : false;

        $activate_access_Seg = array_key_exists("activate_access_Seg", $body) && $body["activate_access_Seg"] ? $body["activate_access_Seg"] : false;
        $from_Seg = array_key_exists("from_Seg", $body) && $body["from_Seg"] ? $body["from_Seg"] : null;
        $to_Seg = array_key_exists("to_Seg", $body) && $body["to_Seg"] ? $body["to_Seg"] : null;

        $activate_access_Ter = array_key_exists("activate_access_Ter", $body) && $body["activate_access_Ter"] ? $body["activate_access_Ter"] : false;
        $from_Ter = array_key_exists("from_Ter", $body) && $body["from_Ter"] ? $body["from_Ter"] : null;
        $to_Ter = array_key_exists("to_Ter", $body) && $body["to_Ter"] ? $body["to_Ter"] : null;

        $activate_access_Qua = array_key_exists("activate_access_Qua", $body) && $body["activate_access_Qua"] ? $body["activate_access_Qua"] : false;
        $from_Qua = array_key_exists("from_Qua", $body) && $body["from_Qua"] ? $body["from_Qua"] : null;
        $to_Qua = array_key_exists("to_Qua", $body) && $body["to_Qua"] ? $body["to_Qua"] : null;

        $activate_access_Qui = array_key_exists("activate_access_Qui", $body) && $body["activate_access_Qui"] ? $body["activate_access_Qui"] : false;
        $from_Qui = array_key_exists("from_Qui", $body) && $body["from_Qui"] ? $body["from_Qui"] : null;
        $to_Qui = array_key_exists("to_Qui", $body) && $body["to_Qui"] ? $body["to_Qui"] : null;

        $activate_access_Sex = array_key_exists("activate_access_Sex", $body) && $body["activate_access_Sex"] ? $body["activate_access_Sex"] : false;
        $from_Sex = array_key_exists("from_Sex", $body) && $body["from_Sex"] ? $body["from_Sex"] : null;
        $to_Sex = array_key_exists("to_Sex", $body) && $body["to_Sex"] ? $body["to_Sex"] : null;

        $activate_access_Sab = array_key_exists("activate_access_Sab", $body) && $body["activate_access_Sab"] ? $body["activate_access_Sab"] : false;
        $from_Sab = array_key_exists("from_Sab", $body) && $body["from_Sab"] ? $body["from_Sab"] : null;
        $to_Sab = array_key_exists("to_Sab", $body) && $body["to_Sab"] ? $body["to_Sab"] : null;

        $activate_access_Dom = array_key_exists("activate_access_Dom", $body) && $body["activate_access_Dom"] ? $body["activate_access_Dom"] : false;
        $from_Dom = array_key_exists("from_Dom", $body) && $body["from_Dom"] ? $body["from_Dom"] : null;
        $to_Dom = array_key_exists("to_Dom", $body) && $body["to_Dom"] ? $body["to_Dom"] : null;

        $week_hours = [
            1 => [ "from" => $from_Seg, "to" => $to_Seg, "active" => $activate_access_Seg],
            2 => [ "from" => $from_Ter, "to" => $to_Ter, "active" => $activate_access_Ter],
            3 => [ "from" => $from_Qua, "to" => $to_Qua, "active" => $activate_access_Qua],
            4 => [ "from" => $from_Qui, "to" => $to_Qui, "active" => $activate_access_Qui],
            5 => [ "from" => $from_Sex, "to" => $to_Sex, "active" => $activate_access_Sex],
            6 => [ "from" => $from_Sab, "to" => $to_Sab, "active" => $activate_access_Sab],
            7 => [ "from" => $from_Dom, "to" => $to_Dom, "active" => $activate_access_Dom],
        ];

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->editUser($userId, $userRepository, $companyId, $editUserId, $departments, $displayName, $activate_access, $week_hours, $grammar_correction));
    }

    public function getEmployees(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getEmployees($userId, $companyId));
    }

    public function getAllCompanyDepartments(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getAllCompanyDepartments($userId, $companyId));
    }

    public function getUserAccessTime(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $employeeId = $route->getArgument('employeeId');

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getUserAccessTime($userId, $companyId, $employeeId));
    }

    public function saveURA(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $menus = array_key_exists("menus", $body) ? $body["menus"] : [];
        $device_id = array_key_exists("device_id", $body) ? $body["device_id"] : '';

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->saveURA($userId, $companyId, $menus, $device_id));
    }

    public function getURA(Request $request, Response $response, array $args): Response
    {
        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);
        $routeContext = RouteContext::fromRequest($request); 
        $route = $routeContext->getRoute();

        $device_id = $route->getArgument('device_id');

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getURA($userId, $companyId, $device_id));
    }

    public function getAllDevices(Request $request, Response $response, array $args): Response
    {
        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getAllDevices($companyId, $userId));
    }

    public function updateDepartment(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $department = array_key_exists("department", $body) ? $body["department"] : [];

        $activate_access = false;
        $week_hours = [];

        if ($department["action"] === "update") {
            $activate_access = array_key_exists("activate_access", $department) && $department["activate_access"] ? $department["activate_access"] : false;

            $activate_access_Seg = array_key_exists("activate_access_Seg", $department) && $department["activate_access_Seg"] ? $department["activate_access_Seg"] : false;
            $from_Seg = array_key_exists("from_Seg", $department) && $department["from_Seg"] ? $department["from_Seg"] : null;
            $to_Seg = array_key_exists("to_Seg", $department) && $department["to_Seg"] ? $department["to_Seg"] : null;

            $activate_access_Ter = array_key_exists("activate_access_Ter", $department) && $department["activate_access_Ter"] ? $department["activate_access_Ter"] : false;
            $from_Ter = array_key_exists("from_Ter", $department) && $department["from_Ter"] ? $department["from_Ter"] : null;
            $to_Ter = array_key_exists("to_Ter", $department) && $department["to_Ter"] ? $department["to_Ter"] : null;

            $activate_access_Qua = array_key_exists("activate_access_Qua", $department) && $department["activate_access_Qua"] ? $department["activate_access_Qua"] : false;
            $from_Qua = array_key_exists("from_Qua", $department) && $department["from_Qua"] ? $department["from_Qua"] : null;
            $to_Qua = array_key_exists("to_Qua", $department) && $department["to_Qua"] ? $department["to_Qua"] : null;

            $activate_access_Qui = array_key_exists("activate_access_Qui", $department) && $department["activate_access_Qui"] ? $department["activate_access_Qui"] : false;
            $from_Qui = array_key_exists("from_Qui", $department) && $department["from_Qui"] ? $department["from_Qui"] : null;
            $to_Qui = array_key_exists("to_Qui", $department) && $department["to_Qui"] ? $department["to_Qui"] : null;

            $activate_access_Sex = array_key_exists("activate_access_Sex", $department) && $department["activate_access_Sex"] ? $department["activate_access_Sex"] : false;
            $from_Sex = array_key_exists("from_Sex", $department) && $department["from_Sex"] ? $department["from_Sex"] : null;
            $to_Sex = array_key_exists("to_Sex", $department) && $department["to_Sex"] ? $department["to_Sex"] : null;

            $activate_access_Sab = array_key_exists("activate_access_Sab", $department) && $department["activate_access_Sab"] ? $department["activate_access_Sab"] : false;
            $from_Sab = array_key_exists("from_Sab", $department) && $department["from_Sab"] ? $department["from_Sab"] : null;
            $to_Sab = array_key_exists("to_Sab", $department) && $department["to_Sab"] ? $department["to_Sab"] : null;

            $activate_access_Dom = array_key_exists("activate_access_Dom", $department) && $department["activate_access_Dom"] ? $department["activate_access_Dom"] : false;
            $from_Dom = array_key_exists("from_Dom", $department) && $department["from_Dom"] ? $department["from_Dom"] : null;
            $to_Dom = array_key_exists("to_Dom", $department) && $department["to_Dom"] ? $department["to_Dom"] : null;

            $week_hours = [
                1 => [ "from" => $from_Seg, "to" => $to_Seg, "active" => $activate_access_Seg],
                2 => [ "from" => $from_Ter, "to" => $to_Ter, "active" => $activate_access_Ter],
                3 => [ "from" => $from_Qua, "to" => $to_Qua, "active" => $activate_access_Qua],
                4 => [ "from" => $from_Qui, "to" => $to_Qui, "active" => $activate_access_Qui],
                5 => [ "from" => $from_Sex, "to" => $to_Sex, "active" => $activate_access_Sex],
                6 => [ "from" => $from_Sab, "to" => $to_Sab, "active" => $activate_access_Sab],
                7 => [ "from" => $from_Dom, "to" => $to_Dom, "active" => $activate_access_Dom],
            ];
        }

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->updateDepartment($companyId, $userId, $department, $activate_access, $week_hours));
    }

    public function deleteDepartment(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request); 
        $route = $routeContext->getRoute();

        $departmentId = $route->getArgument('department_id');

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->deleteDepartment($companyId, $userId, $departmentId));
    }

    public function getAllCompanyTags(Request $request, Response $response, array $args): Response
    {
        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getAllCompanyTags($companyId, $userId));
    }

    public function saveCompanyTags(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        $groups = array_key_exists("groups", $body) ? $body["groups"] : [];

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->saveCompanyTags($companyId, $userId, $groups));
    }

    public function deleteTagGroup(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        $groupId = array_key_exists("groupId", $body) ? $body["groupId"] : 0;

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->deleteTagGroup($companyId, $userId, $groupId));
    }

    public function deleteTag(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        $tagId = array_key_exists("tagId", $body) ? $body["tagId"] : 0;

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->deleteTag($companyId, $userId, $tagId));
    }

    public function getDepartmentAccessTime(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $departmentId = $route->getArgument('departmentId');

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();
        $companyId = $user->getCompanyId();

        return $response->withJson($companyRepository->getDepartmentAccessTime($userId, $companyId, $departmentId));
    }
}
