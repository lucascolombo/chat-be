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

  public function getChats($company = null, $limit = 10, $search = '', $nao_lido = false, $setor = '', $status = '', $tag = '', $user = '', $userId) {
    $message = [ 'success' => false ];

    if ($company === null) return $message;

    $pdo = $this->container->get('db');

    $search_query = "";
    if ($search !== "") {
      $search_query = " AND (cco.client_phone LIKE '%$search%' OR crd.client_name LIKE '%$search%') ";
    }

    $tag_query = "";
    if ($tag !== "") {
      $tag_query = " INNER JOIN client_tags_selected cts ON cts.chat_id = cco.chat_id AND cts.tag_id IN ($tag)";
    }

    $setor_query = "";
    if ($setor !== "") {
      $setor_query = " AND cco.chat_department_id IN ($setor) ";
    }

    $status_query = "";
    if ($status !== "") {
      if ($status == 2) {
        $status_query = " AND cco.chat_department_id = '0' AND cco.employee_id = '0' AND cco.chat_date_close = '0' ";
      }
      else if ($status == 3) {
        $status_query = " AND cco.chat_department_id > '0' AND cco.employee_id = '0' AND cco.chat_date_close = '0' ";
      }
      else if ($status == 1) {
        $status_query = " AND cco.employee_id > '0' AND cco.chat_date_close = '0' ";
      }
      
      if ($status == 4) {
        $status_query = " AND cco.chat_date_close > '0' AND cco.chat_date_close > ( SELECT chat_date_start FROM clients_chats_opened where client_id = cco.client_id ORDER BY chat_date_start DESC LIMIT 1 )";
      }
    }

    $user_query = "";
    if ($user !== "") {
      $user_query = " AND cco.chat_employee_id IN ($user) ";
    }

    $nao_lido_query = "";
    if ($nao_lido) {
      $nao_lido_query = " AND cco.chat_last_message_add > cco.chat_employee_last_seen AND chat_last_message_who = 'C' ";
    }

    $stmt = $pdo->query("
      SELECT 
        cco.chat_id as id,
        cco.client_id,
        IF(crd.client_name = '', cco.client_phone, crd.client_name) as exhibition_name,
        cco.client_phone,
        crd.client_avatar as avatar,
        cco.chat_last_message_add as last_time,
        crd.department_fixed,
        crd.employee_fixed,
        crd.employee_fixed_max_date,
        cco.chat_standby as queue,
        cco.chat_employee_id,
        IF(cco.chat_employee_id = '$userId', 1, 0) as chat_is_mine,
        IF(cco.chat_standby = '$userId', 1, 0) as queue_is_mine,
        (SELECT employee_name FROM employee_details WHERE employee_id = cco.chat_standby) as queue_user,
        (SELECT GROUP_CONCAT(tag_id) FROM `client_tags_selected` WHERE chat_id = cco.chat_id) as tags,
        (SELECT COUNT(*) FROM clients_messages cm WHERE cm.chat_id = cco.chat_id AND message_status IN ('RECEIVED')) as count
      FROM clients_chats_opened cco
      INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
      {$tag_query}
      WHERE cco.chat_date_close <= 0
      {$search_query}
      {$setor_query}
      {$status_query}
      {$user_query}
      {$nao_lido_query}
      AND cco.company_id = '$company'
      AND cco.company_id IN (
        SELECT invitations_company_id
        FROM company_invitations 
        WHERE invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      )
      LIMIT {$limit}
    ");
    $fetch = $stmt->fetchAll();
    $arr = [];

    foreach ($fetch as $single) {
      $element = $single;
      $element["last_time"] = date("d/m H:i", $element["last_time"]);
      $arr[] = $element;
    }

    $message = [ 'success' => true, 'chats' => $arr, 'company'=> $company ];

    return $message;
  }

  public function getAllMessages($id, $limit = 50, $userId, $companyId) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
      SELECT 
        cm.message_id_external,
        cm.who_sent,
        cm.message_type_detail as message,
        cm.system_log,
        ed.employee_name,
        cm.message_created,
        cm.message_status_time,
        cm.message_status,
        cm.message_type
      FROM clients_messages cm
      LEFT JOIN employee_details ed ON ed.employee_id = cm.who_sent
      INNER JOIN clients_registered_details crd ON crd.client_id = cm.client_id
      INNER JOIN clients_chats_opened cco ON cco.chat_id = cm.chat_id
      WHERE crd.client_id = '$id'
      AND cm.company_id = '$companyId'
      AND cm.company_id IN (
        SELECT invitations_company_id
        FROM company_invitations 
        WHERE invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      )
      ORDER BY cm.message_created ASC
      LIMIT {$limit}
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

  public function saveTags($id, $tags, $companyId, $userId) {
    $message = [ 'success' => false ];

    if ($tags !== null) {
      $pdo = $this->container->get('db');

      $stmt = $pdo->prepare("
        DELETE cts FROM client_tags_selected cts
        INNER JOIN company_invitations ci ON ci.invitations_company_id = cts.company_id AND ci.invitations_employee_id = '$userId'
        WHERE cts.chat_id = ?
      ");
      $stmt->execute([$id]);

      foreach ($tags as $tag) {
        $stmt = $pdo->prepare("
          INSERT INTO client_tags_selected (tag_id, chat_id, company_id, employee_id) 
          SELECT ?, ?, ?, ? FROM company_invitations 
          WHERE invitations_company_id = '$companyId' AND invitations_employee_id = '$userId'
          LIMIT 1
        ");
        $stmt->execute([$tag, $id, $companyId, $userId]);
      }

      $message["success"] = true;
    }

    return $message;
  }

  public function assignUser($id, $assignedUserId, $assignedDate, $companyId, $userId) {
    $message = [ 'success' => false ];

    if ($assignedUserId !== null && $assignedDate !== null) {
      $pdo = $this->container->get('db');

      $stmt = $pdo->prepare("
        UPDATE clients_registered_details as crd
        INNER JOIN company_invitations ci ON ci.invitations_company_id = crd.company_id AND ci.invitations_employee_id = ?
        SET crd.employee_fixed = ?, crd.employee_fixed_max_date = ?
        WHERE ci.invitations_company_id = ? AND ci.invitations_employee_id = ?
        AND crd.client_id = ?
      ");
      $stmt->execute([$userId, $assignedUserId, $assignedDate, $companyId, $userId, $id]);

      $message["success"] = true;
    }

    return $message;
  }

  public function assignSetor($id, $assignedSetorId, $companyId, $userId) {
    $message = [ 'success' => false ];

    if ($assignedSetorId !== null) {
      $pdo = $this->container->get('db');

      $stmt = $pdo->prepare("
        UPDATE clients_registered_details as crd
        INNER JOIN company_invitations ci ON ci.invitations_company_id = crd.company_id AND ci.invitations_employee_id = ?
        SET crd.department_fixed = ?
        WHERE ci.invitations_company_id = ? AND ci.invitations_employee_id = ?
        AND crd.client_id = ?
      ");
      $stmt->execute([$userId, $assignedSetorId, $companyId, $userId, $id]);

      $message["success"] = true;
    }

    return $message;
  }

  public function addToQueue($id, $userId, $companyId) {
    $message = [ 'success' => false ];

    if ($companyId !== null) {
      $pdo = $this->container->get('db');

      $stmt = $pdo->prepare("
        UPDATE clients_chats_opened as cco
        INNER JOIN company_invitations ci ON ci.invitations_company_id = cco.company_id AND ci.invitations_employee_id = ?
        SET cco.chat_standby = ?
        WHERE ci.invitations_company_id = ?
        AND cco.chat_id = ?
      ");
      $stmt->execute([$userId, $userId, $companyId, $id]);

      $message["queue"] = $userId;
      $message["success"] = true;
    }

    return $message;
  }

  public function removeToQueue($id, $userId, $companyId) {
    $message = [ 'success' => false ];

    if ($companyId !== null) {
      $pdo = $this->container->get('db');

      $stmt = $pdo->prepare("
        UPDATE clients_chats_opened as cco
        INNER JOIN company_invitations ci ON ci.invitations_company_id = cco.company_id AND ci.invitations_employee_id = ?
        SET cco.chat_standby = 0
        WHERE ci.invitations_company_id = ?
        AND cco.chat_id = ?
      ");
      $stmt->execute([$userId, $companyId, $id]);

      $message["success"] = true;
    }

    return $message;
  }
}