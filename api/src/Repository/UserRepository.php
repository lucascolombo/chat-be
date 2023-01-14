<?php
declare(strict_types=1);
namespace App\Repository;

use Pimple\Psr11\Container;
use App\Lib\User;

final class UserRepository
{
  private $container;

  public function __construct(Container $container) {   
      $this->container= $container;
  }

  public function getUserByEmail($email): User {
    $pdo = $this->container->get('db');
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email=?");
    $stmt->execute([$email]);
    $fetch = $stmt->fetch();

    if ($fetch) return new User($fetch["id"], $fetch["email"], $fetch["password"], $fetch["created_at"], $fetch["logged_in"]);

    return null;
  }

  public function updateUserLoggedIn(User $user, \DateTimeImmutable $issuedAt): bool {
    $id = $user->getId();
    $logged_in = $issuedAt->getTimestamp();
    $pdo = $this->container->get('db');
    $stmt = $pdo->prepare("UPDATE user SET logged_in = ? WHERE id = ?");
    $stmt->execute([$logged_in, $id]); 
    
    return $stmt->fetch();
  }
}