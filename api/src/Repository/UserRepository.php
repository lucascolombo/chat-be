<?php
declare(strict_types=1);
namespace App\Repository;

use Pimple\Psr11\Container;
use App\Lib\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Lib\Encrypt;

final class UserRepository
{
  private $container;

  public function __construct(Container $container) {   
      $this->container= $container;
  }

  public function getUserById($id): User {
    $pdo = $this->container->get('db');
    $stmt = $pdo->prepare("SELECT * FROM employee_details WHERE employee_id=?");
    $stmt->execute([$id]);
    $fetch = $stmt->fetch();

    if ($fetch) return new User($fetch["employee_id"], $fetch["employee_mail"], $fetch["employee_password"], $fetch["logged_in"], $fetch["expire_at"]);

    return null;
  }

  public function getUserByEmail(string $email): User {
    $pdo = $this->container->get('db');
    $stmt = $pdo->prepare("SELECT * FROM employee_details WHERE employee_mail=?");
    $stmt->execute([$email]);
    $fetch = $stmt->fetch();

    if ($fetch) return new User($fetch["employee_id"], $fetch["employee_mail"], $fetch["employee_password"], $fetch["logged_in"], $fetch["expire_at"]);

    return null;
  }

  public function updateUserLoggedIn(User $user, \DateTimeImmutable $issuedAt, int $expire): bool {
    $id = $user->getId();
    $logged_in = $issuedAt->getTimestamp();
    $pdo = $this->container->get('db');
    $stmt = $pdo->prepare("UPDATE employee_details SET logged_in = ?, expire_at = ? WHERE employee_id = ?");
    
    return $stmt->execute([$logged_in, $expire, $id]); 
  }

  public function getUserByHeaders($request) {
    $headers = $request->getHeaders();

    if (!preg_match('/Bearer\s(\S+)/', $headers['Authorization'][0], $matches)) {
      return null;
    }

    $jwt = $matches[1];

    if (!$jwt) {
      return null;
    }

    $token = JWT::decode($jwt, new Key($_SERVER['JWT_SECRET_KEY'], 'HS512'));
    $now = new \DateTimeImmutable();
    $server = $request->getServerParams();

    if ($token->iss !== $server['HTTP_HOST'] || $token->nbf > $now->getTimestamp() || $token->exp < $now->getTimestamp()) {
      return null;
    }

    $logged_in = $token->iat;
    $expire_at = $token->exp;
    $email = $token->userName;
    $companyId = Encrypt::decode($token->companyId);

    $user = $this->getUserByEmail($email);
    $user->setCompanyId($companyId);

    if ($user->getLoggedIn() != $logged_in || $user->getExpireAt() != $expire_at) {
      return null;
    }
    
    return $user;
  }

  public function getUserNameById($userId) {
    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT employee_name
      FROM employee_details
      WHERE employee_id = '$userId'
    ");
    $employee = $stmt->fetch();
    
    return $employee['employee_name'];
  }
}