<?php
declare(strict_types=1);
namespace App\Repository;

use Pimple\Psr11\Container;

final class ClientRepository
{
  private $container;

  public function __construct(Container $container) {   
      $this->container= $container;
  }

  public function updateDisplayName($id, $name): bool {
    if ($name !== null) {
        $pdo = $this->container->get('db');
        $stmt = $pdo->prepare("UPDATE clients_registered_details SET client_name = ? WHERE client_id = ?");
    
        return $stmt->execute([$name, $id]); 
    }

    return false;
  }
}