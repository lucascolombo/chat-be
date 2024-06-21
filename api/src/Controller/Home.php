<?php
declare(strict_types=1);
namespace App\Controller;

use App\CustomResponse as Response;
use Pimple\Psr11\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\Repository\UserRepository;
use App\Repository\CompanyRepository;
use App\Lib\Encrypt;

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
        $message = [ 'success' => false, 'error' => 'DATA_NOT_FOUND_ERROR', 'companies' => [] ];
        $body = $request->getParsedBody();

        $email = array_key_exists("email", $body) ? $body["email"] : null;
        $password = array_key_exists("password", $body) ? $body["password"] : null;

        if ($email && $password) {
            $userRepository = new UserRepository($this->container);
            $companyRepository = new CompanyRepository($this->container);
            $user = $userRepository->getUserByEmail($email);

            if ($user && password_verify($password, $user->getPassword())) {
                $message['success'] = true;
                $message['user'] = $companyRepository->getUserCompanies($user->getId());
            }
            else {
                $message['success'] = false;
                $message['error'] = 'USER_LOGIN_ERROR';
            }
        }
        else {
            $message['success'] = false;
            $message['error'] = 'DATA_NOT_FOUND_ERROR';
        }

        return $response->withJson($message);
    }

    public function loginWithCompany(Request $request, Response $response): Response
    {
        $message = [ 'success' => false, 'error' => 'DATA_NOT_FOUND_ERROR' ];
        $body = $request->getParsedBody();

        $email = array_key_exists("email", $body) ? $body["email"] : null;
        $password = array_key_exists("password", $body) ? $body["password"] : null;
        $companyIdEncoded = array_key_exists("companyId", $body) ? $body["companyId"] : null;
        $companyId = Encrypt::decode($companyIdEncoded);

        if ($email && $password && $companyId) {
            $userRepository = new UserRepository($this->container);
            $user = $userRepository->getUserByEmail($email);
            $userId = $user->getId();

            if ($user && password_verify($password, $user->getPassword())) {
                $companyRepository = new CompanyRepository($this->container);
                $issuedAt = new \DateTimeImmutable();
                $expire = $issuedAt->modify('+24 hours')->getTimestamp();

                if ($companyRepository->userRestrictAccess($userId, $companyId)) {
                    $access_time = $companyRepository->getEmployeeAccessTime($userId, $companyId);

                    if ($access_time != null) {
                        $issuedAt = new \DateTimeImmutable();
                        $time = explode(":", $access_time);
                        $h = (int)$time[0];
                        $m = (int)$time[1];
                        $s = (int)$time[2];
                        $expire = $issuedAt->setTime($h, $m, $s)->getTimestamp();
                        $jwt = Encrypt::createJWT($request, $user->getEmail(), $issuedAt, $expire, $companyIdEncoded);

                        $userRepository->updateCompanyOnlineStatus($userId, $companyId);
                        if ($user->getLoggedIn() == 0) $userRepository->acceptCompanyInvitations($userId, $companyId);

                        $message['success'] = $userRepository->updateUserLoggedIn($user, $issuedAt, $expire);
                        $message['jwt'] = $jwt;
                    }
                    else {
                        $message['success'] = false;
                        $message['error'] = 'USER_CANT_LOGIN_DATETIME';
                    }
                }
                else {
                    $jwt = Encrypt::createJWT($request, $email, $issuedAt, $expire, $companyIdEncoded);

                    $userRepository->updateCompanyOnlineStatus($userId, $companyId);
                    if ($user->getLoggedIn() == 0) $userRepository->acceptCompanyInvitations($userId, $companyId);

                    $message['success'] = $userRepository->updateUserLoggedIn($user, $issuedAt, $expire);
                    $message['jwt'] = $jwt;
                }
            }
            else {
                $message['success'] = false;
                $message['error'] = 'USER_LOGIN_ERROR';
            }
        }
        else {
            $message['success'] = false;
            $message['error'] = 'DATA_NOT_FOUND_ERROR';
        }

        return $response->withJson($message);
    }
}
