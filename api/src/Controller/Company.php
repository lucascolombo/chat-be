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
}