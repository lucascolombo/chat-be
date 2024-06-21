<?php
declare(strict_types=1);
namespace App\Repository;

use Pimple\Psr11\Container;
use App\Lib\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Lib\Encrypt;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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

  public function getUserByEmail($email) {
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

  public function updateCompanyOnlineStatus($userId, $companyId) {
    $pdo = $this->container->get('db');
    $stmt = $pdo->prepare("UPDATE employee_details SET company_online_status = ? WHERE employee_id = ?");
    
    return $stmt->execute([$companyId, $userId]); 
  }

  public function changePassword($userId, $password) {
    $message = [ 'success' => false ];

    $new_password = password_hash($password, PASSWORD_DEFAULT);

    $pdo = $this->container->get('db');
    $stmt = $pdo->prepare("UPDATE employee_details SET employee_password = ? WHERE employee_id = ?");
    $message['success'] = $stmt->execute([$new_password, $userId]);

    return $message;
  }

  public function acceptCompanyInvitations($userId, $companyId) {
    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
      SELECT logged_in
      FROM employee_details
      WHERE employee_id = '$userId'
    ");
    $employee = $stmt->fetch();

    $stmt = $pdo->prepare("UPDATE company_invitations SET invitations_accept = ? WHERE invitations_company_id = ? AND invitations_employee_id = ?");
    
    return $stmt->execute([time(), $companyId, $userId]); 
  }

  public function recoverPassword($email) {
    $message = [ "success" => true ];

    $user = $this->getUserByEmail($email);

    if ($user) {
      $pdo = $this->container->get('db');
      $nome = $this->getUserNameById($user->getId());

      $hash = null;
      $exists = true;
      while ($exists) {
        $hash = bin2hex(random_bytes(16));
        
        $stmt = $pdo->query("
          SELECT 1
          FROM employee_forgot_password
          WHERE id = '$hash'
        ");

        $exists = $stmt->fetch();
      }

      $message["hash"] = $hash;

      $stmt = $pdo->query("
        UPDATE employee_forgot_password 
        SET expired = 1 
        WHERE employee_id = '" . $user->getId() . "'
      ");

      $stmt = $pdo->query("
        INSERT INTO employee_forgot_password (id, time, employee_id)
        VALUES ('" . $hash . "', '" . time() . "', '" . $user->getId() . "')
      ");

      $mail = new PHPMailer(true);
      $mail->isSMTP();
      $mail->Host       = 'smtp.mail.me.com';
      $mail->SMTPAuth   = true;
      $mail->Username   = 'rael.rogowski@icloud.com';
      $mail->Password   = 'ezea-ffux-fmfs-nspk';
      $mail->SMTPSecure = 'tls';
      $mail->SMTPKeepAlive = true;
      $mail->Port       = 587;

      $mail->setFrom('multichat@multichat.app.br', 'MultiChat');
      $mail->addAddress($email);
      $mail->addReplyTo('no-reply@multichat.app.br', 'MultiChat');
      $mail->isHTML(true);
      $mail->Subject = "MultiChat | Esqueci minha senha";
      $mail->Body = "Olá {$nome},<br /> você solicitou o email de esqueci minha senha.<br /><br />Para alterar sua senha clique em <a href='https://multichat.app.br/recover-password/{$hash}' target='_blank'>https://multichat.app.br/recover-password/{$hash}</a>.";
      $mail->send();
    }

    return $message;
  }

  public function recoverUpdatePassword($hash, $password) {
    $message = [ "success" => false ];

    if ($hash && $password) {
      $pdo = $this->container->get('db');
      $timeLimit = time() - 30 * 60;
      
      $stmt = $pdo->query("
        SELECT employee_id
        FROM employee_forgot_password
        WHERE id = '$hash'
        AND time >= '$timeLimit'
        AND expired = 0
      ");
      $fetch = $stmt->fetch();

      if ($fetch) {
        $employee_id = $fetch["employee_id"];

        $this->changePassword($employee_id, $password);

        $pdo = $this->container->get('db');
        $stmt = $pdo->query("
          UPDATE employee_forgot_password 
          SET expired = 1 
          WHERE employee_id = '" . $employee_id . "'
        ");

        $message = [ "success" => true ];
      }
    }

    return $message;
  }
}