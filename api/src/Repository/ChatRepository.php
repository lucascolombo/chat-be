<?php
declare(strict_types=1);
namespace App\Repository;

use Pimple\Psr11\Container;
use App\Lib\Chat;

final class ChatRepository
{
  private $container;

  public function __construct(Container $container) {   
      $this->container= $container;
  }

  public function getChats() {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');
    //AND cco.company_id = X AND cco.chat_departament_id = Y
    $stmt = $pdo->query("
      SELECT 
        cco.chat_id as id,
        IF(crd.client_name = '', cco.client_phone, crd.client_name) as exhibition_name,
        crd.client_avatar as avatar,
        cco.chat_last_message_add as last_time,
        (SELECT COUNT(*) FROM clients_messages cm WHERE cm.chat_id = cco.chat_id AND message_status IN ('RECEIVED')) as count
      FROM clients_chats_opened cco
      INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
      WHERE cco.chat_date_close <= 0
    ");
    $fetch = $stmt->fetchAll();
    $arr = [];

    foreach ($fetch as $single) {
      $element = $single;
      $element["last_time"] = date("d/m H:i", $element["last_time"]);
      $arr[] = $element;
    }

    $message = [ 'success' => true, 'chats' => $arr ];

    return $message;
  }

  public function getAllMessages($id) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');
    //AND cco.company_id = X AND cco.chat_departament_id = Y
    $stmt = $pdo->query("
      SELECT 
        cm.message_id_external,
        cm.who_sent,
        cm.message_type_detail as message,
        cm.system_log,
        ed.employee_name,
        cm.message_created,
        cm.message_status_time,
        cm.message_status
      FROM clients_messages cm
      LEFT JOIN employee_details ed ON ed.employee_id = cm.who_sent
      WHERE cm.chat_id = '$id'
      ORDER BY cm.message_created ASC
    ");
    $fetch = $stmt->fetchAll();

    $arr = [];

    foreach ($fetch as $single) {
      $element = $single;
      $element["datetime"] = date("d/m/Y H:i:s", $element["message_created"]);
      $arr[] = $element;
    }

    $message = [ 'success' => true, 'messages' => $arr ];

    return $message;
  }
}