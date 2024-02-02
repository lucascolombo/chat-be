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
        $companyId = Encrypt::decode($route->getArgument('id'));

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
        
        $companyId = Encrypt::decode($route->getArgument('id'));

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($companyRepository->uploadFile($userId, $companyId, $files));
    }

    public function deleteFile(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $body = $request->getParsedBody();
        
        $companyId = Encrypt::decode($route->getArgument('id'));
        $fileId = $route->getArgument('fileId');

        $deleteAll = array_key_exists("deleteAll", $body) ? $body["deleteAll"] : 0;

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($companyRepository->deleteFile($userId, $companyId, $fileId, $deleteAll));
    }

    public function createFile(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();
        $body = $request->getParsedBody();

        $companyId = Encrypt::decode($route->getArgument('id'));

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

        return $response->withJson($companyRepository->createFile($userId, $companyId, $file, $label, $setor, $tag, $where, $fileSize, $fileType));
    }

    public function getAllFixedFiles(Request $request, Response $response, array $args): Response
    {
        $routeContext = RouteContext::fromRequest($request);                                                                                                             
        $route = $routeContext->getRoute();

        $companyId = Encrypt::decode($route->getArgument('id'));

        $userRepository = new UserRepository($this->container);
        $companyRepository = new CompanyRepository($this->container);

        $user = $userRepository->getUserByHeaders($request);
        $userId = $user->getId();

        return $response->withJson($companyRepository->getAllFixedFiles($userId, $companyId));
    }
}
