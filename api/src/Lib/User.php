<?php
declare(strict_types=1);
namespace App\Lib;

final class User
{
  private $id, $email, $password, $logged_in;

  //$password = password_hash($password, PASSWORD_DEFAULT);

  public function __construct(
    int $id, 
    string $email, 
    string $password, 
    string $logged_in
  ) {   
    $this->id= $id;
    $this->email= $email;
    $this->password= $password;
    $this->logged_in= $logged_in;
  }

  public function getId() {
    return $this->id;
  }

  public function getEmail() {
    return $this->email;
  }

  public function getPassword() {
    return $this->password;
  }

  public function getLoggedIn() {
    return $this->logged_in;
  } 
}