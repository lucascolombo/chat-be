<?php
declare(strict_types=1);
namespace App\Repository;

use Pimple\Psr11\Container;
use App\Lib\CryptContent;

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

  public function finish($id, $companyId, $userId) {
    $message = [ 'success' => false ];

    if ($companyId !== null) {
      $pdo = $this->container->get('db');

      $stmt = $pdo->query("
        SELECT employee_name
        FROM employee_details
        WHERE employee_id = '$userId'
      ");
      $employee = $stmt->fetch();
      $usuario = $employee['employee_name'];

      $stmt = $pdo->query("
        SELECT *
        FROM clients_chats_opened cco
        INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
        WHERE cco.chat_date_close = 0
        AND crd.client_id = '$id'
        ORDER BY cco.chat_id DESC
        LIMIT 1
      ");
      $lastChat = $stmt->fetch();

      $datetime = time();

      $stmt = $pdo->prepare("
        UPDATE clients_chats_opened as cco
        INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
        SET cco.chat_date_close = ?
        WHERE cco.chat_date_close = 0
        AND crd.client_id = ?
      ");
      $stmt->execute([$datetime, $id]);

      if ($lastChat['chat_employee_id'] == 0) {
        $stmt = $pdo->prepare("
          UPDATE clients_chats_opened as cco
          SET cco.chat_employee_id = ?
          WHERE cco.chat_id = ?
        ");
        $stmt->execute([$userId, $lastChat['chat_id']]);
      }

      $messageTypeDetail = "Atendimento Finalizado por $usuario";
      $security = new CryptContent($companyId);
      $messageTypeDetail = $security->encrypt($messageTypeDetail);

      $stmt = $pdo->prepare("
        INSERT INTO clients_messages (chat_id, client_id, client_phone, company_id, department_id, who_sent, message_status, message_status_time, message_created, message_type_detail, message_device_id, system_log) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$lastChat['chat_id'], $id, $lastChat['client_phone'], $companyId, $lastChat['chat_department_id'], $userId, 'SENT-DEVICE', $datetime, $datetime, $messageTypeDetail, $lastChat['device_id'], '1']);
      
      $standBy = $lastChat['chat_standby'];

      if ($standBy != '0')
        $this->start($id, $companyId, $standBy);

      $message["success"] = true;
    }

    return $message;
  }

  public function start($id, $companyId, $userId) {
    $message = [ 'success' => false ];

    if ($companyId !== null) {
      $pdo = $this->container->get('db');

      $stmt = $pdo->query("
        SELECT employee_name
        FROM employee_details
        WHERE employee_id = '$userId'
      ");
      $employee = $stmt->fetch();
      $usuario = $employee['employee_name'];

      $stmt = $pdo->query("
        SELECT *
        FROM clients_chats_opened cco
        INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
        WHERE crd.client_id = '$id'
        ORDER BY cco.chat_id DESC
        LIMIT 1
      ");
      $lastChat = $stmt->fetch();

      $datetime = time() + 1;

      $stmt = $pdo->prepare("
        UPDATE clients_chats_opened as cco
        INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
        SET cco.chat_date_close = ?,
        chat_last_message_add = '$datetime'
        WHERE cco.chat_date_close = 0
        AND crd.client_id = ?
      ");
      $stmt->execute([$datetime, $id]);

      $stmt = $pdo->prepare("
        INSERT INTO clients_chats_opened (who_start, client_id, company_id, device_id, client_phone, chat_date_start, chat_department_id, chat_employee_id, chat_employee_last_seen, chat_last_message_add, chat_last_message_who, ura_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute(['1', $id, $companyId, $lastChat['device_id'], $lastChat['client_phone'], $datetime, $lastChat['chat_department_id'], $userId, $datetime, $lastChat['chat_last_message_add'], $lastChat['chat_last_message_who'], '1']);

      $newChatId = $pdo->lastInsertId();

      $messageTypeDetail = "Atendimento Iniciado por $usuario";
      $security = new CryptContent($companyId);
      $messageTypeDetail = $security->encrypt($messageTypeDetail);

      $stmt = $pdo->prepare("
        INSERT INTO clients_messages (chat_id, client_id, client_phone, company_id, department_id, who_sent, message_status, message_status_time, message_created, message_type_detail, message_device_id, system_log) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$newChatId, $id, $lastChat['client_phone'], $companyId, $lastChat['chat_department_id'], $userId, 'SENT-DEVICE', $datetime, $datetime, $messageTypeDetail, $lastChat['device_id'], '1']);


      $message["success"] = true;
    }

    return $message;
  }
}