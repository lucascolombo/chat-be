<?php
declare(strict_types=1);
namespace App\Repository;

use Pimple\Psr11\Container;
use App\Lib\Chat;
use Slim\Psr7\UploadedFile;
use App\Repository\UserRepository;
use App\Lib\CryptContent;

class SendSimpleText { 
  private $instancia; 
  private $token; 
  private $phone; 
  private $message; 
  private $ReplyMessageId; 

  public function __construct($instancia,$token,$phone,$message,$ReplyMessageId){
      $this->instancia = $instancia;
      $this->token = $token; 
      $this->phone = $phone;
      $this->message = $message;
      $this->ReplyMessageId = $ReplyMessageId;
  }

  public function send() {
      //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}

      $client = curl_init();
  
      $data = json_encode(array(
          "phone"  => $this->phone,
          "message" => $this->message,
          "messageId"  => $this->ReplyMessageId
      ));
  
      curl_setopt_array($client, array(
        CURLOPT_URL => "https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/send-text",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
          "client-token: F10e75314314549299f7236a6f2ac8c5fS",
          "content-type: application/json"
        ),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 5
      ));
  
      $response = curl_exec($client);
      if (curl_error($client)) return null;

      $MessageID = json_decode($response);
      $MessageID = $MessageID->messageId;
      curl_close($client);
  
      return $MessageID;
  }
}

class SendMedia { 
  private $instancia; 
  private $token; 
  private $phone; 
  private $LinkMedia;
  
  public function __construct($instancia,$token,$phone,$LinkMedia){
      $this->instancia = $instancia;
      $this->token = $token; 
      $this->phone = $phone;
      $this->LinkMedia = $LinkMedia;
  }

  public function send($label = "Anexo") {
      //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}
      $extension = strtolower(pathinfo($this->LinkMedia, PATHINFO_EXTENSION));

      if (strpos($extension, 'acc') !== false) {$MediaType = "audio";}
      elseif (strpos($extension, 'amr') !== false) {$MediaType = "audio";}
      elseif (strpos($extension, 'mpeg') !== false) {$MediaType = "audio";}
      elseif (strpos($extension, 'ogg') !== false) {$MediaType = "audio";}
      elseif (strpos($extension, 'webm') !== false) {$MediaType = "audio";}
      elseif (strpos($extension, 'jpeg') !== false) {$MediaType = "image";}
      elseif (strpos($extension, 'jpg') !== false) {$MediaType = "image";}
      elseif (strpos($extension, 'png') !== false) {$MediaType = "image";}
      elseif (strpos($extension, 'webp') !== false) {$MediaType = "image";}
      elseif (strpos($extension, 'mp4') !== false) {$MediaType = "video";}
      elseif (strpos($extension, '3gpp') !== false) {$MediaType = "video";}
      elseif (strpos($extension, 'pdf') !== false) {$MediaType = "document";}
      elseif (strpos($extension, 'doc') !== false) {$MediaType = "document";}
      elseif (strpos($extension, 'dot') !== false) {$MediaType = "document";}
      else{ $MediaType = "document"; }

      $client = curl_init();
  
      $data = json_encode(array(
          "phone"  => $this->phone,
          $MediaType => $this->LinkMedia,
          "caption" =>  $label,
          "fileName" =>  "Arquivo"
      ));

      if ($MediaType == "document"){
        $MediaType = $MediaType."/".$extension;
      }
  
      curl_setopt_array($client, array(
        CURLOPT_URL => "https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/send-".$MediaType,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
          "client-token: F10e75314314549299f7236a6f2ac8c5fS",
          "content-Type: application/json"
        ),
      ));

      $response = curl_exec($client);
      if (curl_error($client)) return null;

      $MessageID = json_decode($response);
      $MessageID = $MessageID->messageId;
      curl_close($client);
  
      return $MessageID;
  }
}

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
        $status_query = " AND cco.chat_date_close > ( SELECT chat_date_start FROM clients_chats_opened where client_id = cco.client_id ORDER BY chat_date_start DESC LIMIT 1 )";
      }
    }
    else {
      $status_query = " AND cco.chat_date_close <= 0 ";
    }

    $user_query = "";
    if ($user !== "") {
      $user_query = " AND cco.chat_employee_id IN ($user) ";
    }

    $nao_lido_query = "";
    if ($nao_lido) {
      $nao_lido_query = " AND cco.chat_last_message_add > cco.chat_employee_last_seen AND chat_last_message_who = 'C' ";
    }

    $query = "
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
      cco.chat_date_start,
      cco.chat_date_close,
      IF(cco.chat_employee_id = '$userId', 1, 0) as chat_is_mine,
      IF(cco.chat_standby = '$userId', 1, 0) as queue_is_mine,
      (SELECT employee_name FROM employee_details WHERE employee_id = cco.chat_standby) as queue_user,
      (SELECT GROUP_CONCAT(tag_id) FROM `client_tags_selected` WHERE chat_id = cco.chat_id) as tags,
      (SELECT COUNT(*) FROM clients_messages WHERE client_id = cco.client_id AND schedule_message > 0 AND message_created > UNIX_TIMESTAMP() AND message_id_external = '0' AND schedule_DeletedBy = '0' AND schedule_SentEarly = '0') as schedule_messages_count,
      (SELECT COUNT(*) FROM clients_messages WHERE client_id = cco.client_id AND message_status IN ('RECEIVED') AND who_sent = 0) as count,
      ccs.status_type,
      ccs.status_label,
      ccs.status_date_validity,
      cco.chat_mark_unread,
      (SELECT message_type_detail FROM clients_messages WHERE chat_id = cco.chat_id AND message_deleted_date = 0 AND message_created <= UNIX_TIMESTAMP() ORDER BY message_id DESC LIMIT 1) as last_message,
      (SELECT message_type FROM clients_messages WHERE chat_id = cco.chat_id AND message_deleted_date = 0 AND message_created <= UNIX_TIMESTAMP() ORDER BY message_id DESC LIMIT 1) as last_message_type,
      (SELECT IFNULL(e.employee_name, 0) FROM clients_messages cm LEFT JOIN employee_details e ON e.employee_id = cm.who_sent WHERE cm.chat_id = cco.chat_id AND cm.message_deleted_date = 0 AND cm.message_created <= UNIX_TIMESTAMP() ORDER BY cm.message_id DESC LIMIT 1) as last_message_user,
      cco.chat_standby,
      crd.department_fixed,
      crd.employee_fixed
    FROM clients_chats_opened cco
    INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
    LEFT JOIN clients_chats_status ccs ON ccs.status_chat_id = cco.chat_id AND ccs.status_date_finished = 0
    {$tag_query}
    WHERE cco.company_id = '$company'
    {$search_query}
    {$setor_query}
    {$status_query}
    {$user_query}
    {$nao_lido_query}
    AND cco.company_id IN (
      SELECT invitations_company_id
      FROM company_invitations 
      WHERE invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    )
    GROUP BY cco.client_id
    ORDER BY chat_last_message_add DESC
    LIMIT {$limit}
  ";

    $stmt = $pdo->query($query);
    $fetch = $stmt->fetchAll();
    $arr = [];

    $security = new CryptContent($company);

    foreach ($fetch as $single) {
      $element = $single;
      $decrypt = $security->decrypt($element['last_message']);

      $element['last_message'] = !$decrypt ? $element['last_message'] : $decrypt;
      $element["last_time"] = date("d/m H:i", $element["last_time"]);
      $element["status_date_validity"] = date("d/m/Y H:i", $element["status_date_validity"]);
      $arr[] = $element;
    }

    $message = [ 'success' => true, 'chats' => $arr, 'company'=> $company, 'query' => $query ];

    return $message;
  }

  public function getSingleChat($id, $userId) {
    $message = [ 'success' => false, 'chat' => [] ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT 
        cco.chat_id as id,
        cco.client_id,
        IF(crd.client_name = '', cco.client_phone, crd.client_name) as exhibition_name,
        cco.client_phone,
        cco.device_id,
        crd.client_avatar as avatar,
        cco.chat_last_message_add as last_time,
        crd.department_fixed,
        crd.employee_fixed,
        crd.employee_fixed_max_date,
        (SELECT employee_name FROM employee_details WHERE employee_id = crd.employee_fixed) as employee_fixed_name,
        (SELECT departments_name FROM company_departments WHERE departments_id = crd.department_fixed) as department_fixed_name,
        cco.chat_standby as queue,
        cco.chat_employee_id,
        (SELECT employee_name FROM employee_details WHERE employee_id = cco.chat_employee_id) as chat_employee_name,
        (SELECT employee_online_status FROM employee_details WHERE employee_id = cco.chat_employee_id) as chat_employee_online_status,
        cco.chat_date_close,
        cco.chat_date_start,
        IF(cco.chat_employee_id = '$userId', 1, 0) as chat_is_mine,
        IF(cco.chat_standby = '$userId', 1, 0) as queue_is_mine,
        (SELECT employee_name FROM employee_details WHERE employee_id = cco.chat_standby) as queue_user,
        (SELECT GROUP_CONCAT(tag_id) FROM `client_tags_selected` WHERE chat_id = cco.chat_id) as tags,
        (SELECT COUNT(*) FROM clients_messages WHERE client_id = cco.client_id AND schedule_message > 0 AND message_created > UNIX_TIMESTAMP() AND message_id_external = '0' AND schedule_DeletedBy = '0' AND schedule_SentEarly = '0') as schedule_messages_count,
        (SELECT COUNT(*) FROM clients_messages WHERE chat_id = cco.chat_id AND message_status IN ('RECEIVED')) as count,
        ccs.status_type,
        ccs.status_label,
        ccs.status_date_validity,
        ccs.status_message_toEndChat,
        ccs.status_abortIFmessageReceived
      FROM clients_chats_opened cco
      INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
      LEFT JOIN clients_chats_status ccs ON ccs.status_chat_id = cco.chat_id AND ccs.status_date_finished = 0
      WHERE cco.client_id = '$id'
      AND cco.company_id IN (
        SELECT invitations_company_id
        FROM company_invitations 
        WHERE invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      )
      ORDER BY cco.chat_id DESC
      LIMIT 1
    ");
    $chat = $stmt->fetch();

    $message = [ 'success' => true, 'chat' => $chat ];

    return $message;
  }

  public function getAllMessages($id, $limit = 50, $userId, $companyId) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');

    $stmt = $pdo->prepare("
      UPDATE employee_details 
      SET employee_online_status = ?
      WHERE employee_id = ?
    ");
    $stmt->execute([time(), $userId]);

    $datetime = time();

    $stmt = $pdo->query("
      SELECT 
        cm.message_id_external,
        cm.who_sent,
        cm.message_type_detail as message,
        cm.metatags_url,
        cm.system_log,
        ed.employee_name,
        cm.message_created,
        cm.message_status_time,
        cm.message_status,
        cm.message_type,
        cm.message_deleted_date,
        cm.message_edited_date,
        cm.message_reaction,
        cm.sent_from,
        cm.sendError,
        cd.device_status
      FROM clients_messages cm
      INNER JOIN clients_chats_opened cco ON cco.chat_id = cm.chat_id
      INNER JOIN company_devices cd ON cd.device_id = cco.device_id
      LEFT JOIN employee_details ed ON ed.employee_id = cm.who_sent
      WHERE cm.client_id = '$id'
      AND cm.system_log_hidden <= 0
      AND cm.message_created <= '$datetime'
      AND cm.company_id IN (
        SELECT invitations_company_id
        FROM company_invitations 
        WHERE invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      )
      ORDER BY cm.message_created DESC, cm.message_id ASC
      LIMIT {$limit}
    ");
    $fetch = $stmt->fetchAll();

    $arr = [];

    $security = new CryptContent($companyId);

    foreach ($fetch as $single) {
      $element = $single;
      $decrypt = $security->decrypt($element['message']);

      $element["datetime"] = date("d/m/Y H:i:s", $element["message_created"]);
      $element["datetime_deleted"] = date("d/m/Y H:i:s", $element["message_deleted_date"]);
      $element["datetime_edited"] = date("d/m/Y H:i:s", $element["message_edited_date"]);
      $element['message'] = !$decrypt ? $element['message'] : $decrypt;

      $arr[] = $element;
    }

    $message = [ 'success' => true, 'messages' => array_reverse($arr) ];

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

      if ($assignedUserId === '') {
        $assignedUserId = '0';
        $assignedDate = '0';
      }

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

      if ($assignedSetorId === '') {
        $assignedSetorId = '0';
      }

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

  public function insertSystemLogByChatId($chatId, $message, $userId) {
    $pdo = $this->container->get('db');

    $datetime = time() + 1;

    $stmt = $pdo->query("
      SELECT * FROM clients_chats_opened WHERE chat_id = '$chatId'
    ");
    $lastChat = $stmt->fetch();
    $clientId = $lastChat['client_id'];
    $client_phone = $lastChat['client_phone'];
    $company_id = $lastChat['company_id'];
    $department_id = $lastChat['chat_department_id'];
    $device_id = $lastChat['device_id'];
    $who_sent = $userId;

    $security = new CryptContent($company_id);
    $message = $security->encrypt($message);

    $stmt = $pdo->prepare("
      INSERT INTO clients_messages (chat_id, client_id, client_phone, company_id, department_id, who_sent, message_status, message_status_time, message_created, message_type_detail, message_device_id, system_log) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$chatId, $clientId, $client_phone, $company_id, $department_id, $who_sent, 'SENT-DEVICE', $datetime, $datetime, $message, $device_id, '1']);

    $stmt = $pdo->prepare("
      UPDATE clients_chats_opened 
      SET chat_last_message_add = ?,
      chat_last_message_who = ?
      WHERE chat_id = ?
    ");
    $stmt->execute([$datetime, 'L', $chatId]);
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

      $stmt = $pdo->query("
        SELECT employee_name
        FROM employee_details
        WHERE employee_id = '$userId'
      ");
      $employee = $stmt->fetch();
      $usuario = $employee['employee_name'];

      $this->insertSystemLogByChatId($id, "$usuario entrou na fila de espera.", $userId);

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

      $stmt = $pdo->query("
        SELECT employee_name
        FROM employee_details
        WHERE employee_id = '$userId'
      ");
      $employee = $stmt->fetch();
      $usuario = $employee['employee_name'];

      $this->insertSystemLogByChatId($id, "$usuario saiu da fila de espera.", $userId);

      $message["success"] = true;
    }

    return $message;
  }

  public function transferSetor($id, $setor, $companyId, $userId) {
    $message = [ 'success' => false ];

    if ($setor !== null) {
      $pdo = $this->container->get('db');

      $datetime = time();

      $stmt = $pdo->query("
        SELECT departments_name
        FROM company_departments
        WHERE departments_id = '$setor'
      ");
      $employee = $stmt->fetch();
      $departamento = $employee['departments_name'];

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
        INNER JOIN company_invitations ci ON ci.invitations_company_id = crd.company_id AND ci.invitations_employee_id = '$userId'
        WHERE cco.chat_date_close = 0
        AND crd.client_id = '$id'
        AND ci.invitations_company_id = '$companyId'
        AND ci.invitations_employee_id = '$userId'
        ORDER BY cco.chat_id DESC
        LIMIT 1
      ");
      $lastChat = $stmt->fetch();
      $lastChatId = $lastChat["chat_id"];

      $stmt = $pdo->prepare("
        UPDATE clients_chats_opened as cco
        INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
        SET cco.chat_date_close = ?
        WHERE cco.chat_date_close = 0
        AND crd.client_id = ?
      ");
      $stmt->execute([$datetime, $id]);

      $stmt = $pdo->prepare("
        INSERT INTO clients_chats_opened (who_start, client_id, company_id, device_id, client_phone, chat_date_start, chat_department_id, chat_employee_id, chat_employee_last_seen, chat_last_message_add, chat_last_message_who, ura_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$lastChat['who_start'], $id, $companyId, $lastChat['device_id'], $lastChat['client_phone'], $datetime, $setor, $userId, $datetime, $lastChat['chat_last_message_add'], $lastChat['chat_last_message_who'], '1']);

      $newChatId = $pdo->lastInsertId();

      $messageTypeDetail = "Atendimento Transferido por $usuario para o Departamento $departamento.";
      $security = new CryptContent($companyId);
      $messageTypeDetail = $security->encrypt($messageTypeDetail);

      $stmt = $pdo->prepare("
        INSERT INTO clients_messages (chat_id, client_id, client_phone, company_id, department_id, who_sent, message_status, message_status_time, message_created, message_type_detail, message_device_id, system_log) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$newChatId, $id, $lastChat['client_phone'], $companyId, $setor, $userId, 'SENT-DEVICE', $datetime, $datetime, $messageTypeDetail, $lastChat['device_id'], '1']);
      
      $message["success"] = true;
    }

    return $message;
  }

  public function transferUser($id, $user, $companyId, $userId) {
    $message = [ 'success' => false ];

    if ($user !== null) {
      $pdo = $this->container->get('db');

      $datetime = time();

      $stmt = $pdo->query("
        SELECT employee_name
        FROM employee_details
        WHERE employee_id = '$user'
      ");
      $employee = $stmt->fetch();
      $usuario_transferido = $employee['employee_name'];

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
        INNER JOIN company_invitations ci ON ci.invitations_company_id = crd.company_id AND ci.invitations_employee_id = '$userId'
        WHERE cco.chat_date_close = 0
        AND crd.client_id = '$id'
        AND ci.invitations_company_id = '$companyId'
        AND ci.invitations_employee_id = '$userId'
        ORDER BY cco.chat_id DESC
        LIMIT 1
      ");
      $lastChat = $stmt->fetch();

      $stmt = $pdo->prepare("
        UPDATE clients_chats_opened as cco
        INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
        SET cco.chat_date_close = ?
        WHERE cco.chat_date_close = 0
        AND crd.client_id = ?
      ");
      $stmt->execute([$datetime, $id]);

      $stmt = $pdo->prepare("
        INSERT INTO clients_chats_opened (who_start, client_id, company_id, device_id, client_phone, chat_date_start, chat_department_id, chat_employee_id, chat_employee_last_seen, chat_last_message_add, chat_last_message_who) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$lastChat['who_start'], $id, $companyId, $lastChat['device_id'], $lastChat['client_phone'], $datetime, $lastChat['chat_department_id'], $user, $datetime, $lastChat['chat_last_message_add'], $lastChat['chat_last_message_who']]);

      $newChatId = $pdo->lastInsertId();

      $messageTypeDetail = "Atendimento Transferido por $usuario para $usuario_transferido.";
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

  private function moveUploadedFile($directory, UploadedFile $uploadedFile, $basename, $extension) {
    $filename = $basename . "." . $extension;

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
  }

  public function read($id, $userId, $companyId, $validateUser = true) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');
    $datetime = time();

    $stmt = $pdo->query("
      SELECT *
      FROM clients_chats_opened cco
      INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
      INNER JOIN company_invitations ci ON ci.invitations_company_id = crd.company_id AND ci.invitations_employee_id = '$userId'
      WHERE cco.chat_date_close = 0
      AND crd.client_id = '$id'
      AND ci.invitations_company_id = '$companyId'
      AND ci.invitations_employee_id = '$userId'
      ORDER BY cco.chat_id DESC
      LIMIT 1
    ");
    $lastChat = $stmt->fetch();
    
    if (
      ($companyId !== null) && 
      ($lastChat["chat_employee_id"] == $userId || $validateUser === false)
    ) {
      $stmt = $pdo->prepare("
        UPDATE clients_chats_opened as cco
        SET cco.chat_employee_last_seen = ?
        WHERE cco.chat_id = ?
      ");
      $stmt->execute([$datetime, $lastChat["chat_id"]]);

      $stmt = $pdo->prepare("
        UPDATE clients_messages
        SET message_status = 'READ', 
        message_status_time = ? 
        WHERE client_id = ?
        AND who_sent = 0
      ");

      $stmt->execute([$datetime, $id]);

      $this->updateMarkUnread($lastChat["chat_id"], 0);

      $message["success"] = true;
    }

    return $message;
  }

  private function hasSystemLog($chatId, $userId) {
    $pdo = $this->container->get('db');
    $stm = $pdo->prepare(
      "SELECT chat_id 
      FROM clients_messages 
      WHERE chat_id = :chat_id 
      AND who_sent = :who_sent 
      AND system_log >= 1 
      AND message_created >= UNIX_TIMESTAMP() - 300"
    );
    $stm->execute([':chat_id' => $chatId, ':who_sent' => $userId]);
    $countSystemLog = $stm->rowCount();
    
    if ($countSystemLog == 0) {
      return false;
    }

    return true;
  }

  public function open($id, $userId, $companyId) {
    $message = [ 'success' => false ];

    $userRepository = new UserRepository($this->container);                                                                                
    $usuario = $userRepository->getUserNameById($userId);

    $lastChat = $this->getLastChat($id, $companyId, $userId);

    if (!$this->hasSystemLog($lastChat["chat_id"], $userId)) {
      $this->insertSystemLogByChatId($lastChat["chat_id"], "$usuario visualizou este chat.", $userId);
    }

    $message["success"] = true;

    return $message;
  }

  public function sendMessageWhatsapp($messageId, $id, $userId, $companyId, $text, $media) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');
    $datetime = time();

    $stmt = $pdo->query("
      SELECT *
      FROM clients_chats_opened cco
      INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
      INNER JOIN company_invitations ci ON ci.invitations_company_id = crd.company_id AND ci.invitations_employee_id = '$userId'
      WHERE cco.chat_date_close = 0
      AND crd.client_id = '$id'
      AND ci.invitations_company_id = '$companyId'
      AND ci.invitations_employee_id = '$userId'
      ORDER BY cco.chat_id DESC
      LIMIT 1
    ");
    $lastChat = $stmt->fetch();
    $device_id = $lastChat["device_id"];
    $phone = $lastChat['client_phone'];

    $stmt = $pdo->query("
      SELECT 
        device_login as instancia, 
        device_pass as token,
        device_status as status
      FROM company_devices WHERE device_id = '$device_id' 
    ");
    $device = $stmt->fetch();
    $instancia = $device["instancia"];
    $token = $device["token"];

    if ($media == '1') 
      $sender = new SendMedia($instancia, $token, $phone, $text);
    else {
      $stmt = $pdo->query("
        SELECT 
          employee_displayname
        FROM employee_details WHERE employee_id = '$userId' 
      ");
      $employee = $stmt->fetch();
      $display_name = trim($employee["employee_displayname"]);
      if ($display_name !== "") {
        $text = "*" . $display_name . "*:" . "
" . $text;
      }

      $sender = new SendSimpleText($instancia, $token, $phone, $text, "");
    }

    $messageExternalId = $sender->send();

    if ($messageExternalId) {
      $stmt = $pdo->prepare("
        UPDATE clients_messages
        SET message_id_external = ? 
        WHERE message_id = ?
      ");
      $stmt->execute([$messageExternalId, $messageId]);
    }
    else {
      $stmt = $pdo->prepare("
        UPDATE clients_messages
        SET sendError = ?
        WHERE message_id = ?
      ");
      $stmt->execute(['1', $messageId]);
    }

    $message['success'] = true;

    return $message;
  }

  public function sendMessage($id, $userId, $companyId, $text, $scheduleDate, $message_type) {
    $message = [ 'success' => false, "message" => 0 ];

    if ($companyId !== null) {
      $pdo = $this->container->get('db');
      $datetime = time();

      $stmt = $pdo->query("
        SELECT *
        FROM clients_chats_opened cco
        INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
        INNER JOIN company_invitations ci ON ci.invitations_company_id = crd.company_id AND ci.invitations_employee_id = '$userId'
        WHERE cco.chat_date_close = 0
        AND crd.client_id = '$id'
        AND ci.invitations_company_id = '$companyId'
        AND ci.invitations_employee_id = '$userId'
        ORDER BY cco.chat_id DESC
        LIMIT 1
      ");
      $lastChat = $stmt->fetch();
      $device_id = $lastChat["device_id"];
      $phone = $lastChat['client_phone'];

      $stmt = $pdo->query("
        SELECT 
          device_login as instancia, 
          device_pass as token,
          device_status as status
        FROM company_devices WHERE device_id = '$device_id' 
      ");
      $device = $stmt->fetch();
      $instancia = $device["instancia"];
      $token = $device["token"];
      $status = $device["status"];

      if (trim($text) != "") {
        $security = new CryptContent($companyId);
        $text = $security->encrypt($text);

        $stmt = $pdo->prepare("
          INSERT INTO clients_messages (chat_id, message_id_external, client_id, client_phone, company_id, department_id, who_sent, message_status, message_status_time, message_created, message_type_detail, message_device_id, message_type, schedule_message) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$lastChat["chat_id"], '0', $id, $lastChat['client_phone'], $companyId, $lastChat['chat_department_id'], $userId, 'SENT', $datetime, ($scheduleDate != 0 ? $scheduleDate : time()), $text, $lastChat['device_id'], $message_type, ($scheduleDate != 0 ? time() : '0')]);
        $message["message"] = $scheduleDate == 0 && $status == 0 ? $pdo->lastInsertId() : 0;
      }

      $stmt = $pdo->prepare("
        UPDATE clients_chats_opened as cco
        SET cco.chat_last_message_add = ?, 
        cco.chat_last_message_who = 'E' 
        WHERE cco.chat_id = ?
      ");
      $stmt->execute([$datetime, $lastChat["chat_id"]]);

      $message["success"] = true;
    }

    return $message;
  }

  public function uploadFiles($id, $userId, $companyId, $files) {
    $dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $id;

    $message = [ 'success' => false, "files" => [] ];

    if (!file_exists($dir)) {
      mkdir($dir, 0777, true);
    }

    if ($companyId !== null) {
      $pdo = $this->container->get('db');
      $datetime = time();

      $stmt = $pdo->query("
        SELECT *
        FROM clients_chats_opened cco
        INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
        INNER JOIN company_invitations ci ON ci.invitations_company_id = crd.company_id AND ci.invitations_employee_id = '$userId'
        WHERE cco.chat_date_close = 0
        AND crd.client_id = '$id'
        AND ci.invitations_company_id = '$companyId'
        AND ci.invitations_employee_id = '$userId'
        ORDER BY cco.chat_id DESC
        LIMIT 1
      ");
      $lastChat = $stmt->fetch();

      $index = 0;
      foreach($files["files"] as $file) {
        if ($file->getError() === UPLOAD_ERR_OK) {
          if ($file->getSize() <= 15 * 1024 * 1024) {
            $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
            $datetime = time();
            $basename = $lastChat["chat_id"] . "_" . $index . "_" . $datetime;
            $filename = $this->moveUploadedFile($dir, $file, $basename, $extension);
            $filePath = 'https://' . $_SERVER['HTTP_HOST'] . '/file/' . $id . '/' . $filename;

            $message["files"][] = $filePath;

            $index++;
          }
        }
      }

      $message["success"] = true;
    }

    return $message;
  }

  public function getAllScheduledMessages($id, $userId) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
      SELECT * FROM clients_messages cm
      INNER JOIN employee_details ed ON ed.employee_id = cm.who_sent
      WHERE cm.client_id = '$id'
      AND cm.schedule_message > 0 
      AND cm.message_created > UNIX_TIMESTAMP() 
      AND cm.message_id_external = '0'
      AND cm.schedule_DeletedBy = '0'
      AND cm.schedule_SentEarly = '0'
      AND cm.company_id IN (
        SELECT invitations_company_id
        FROM company_invitations 
        WHERE invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      )
      ORDER BY cm.schedule_message ASC
    ");
    $fetch = $stmt->fetchAll();
    $arr = [];

    foreach ($fetch as $single) {
      $element = $single;
      $security = new CryptContent($element['company_id']);
      $decrypt = $security->decrypt($element['message_type_detail']);
      // message_created é a data em que a mensagem vai entrar no chat então é a data agendada
      // schedule_message é a data em que o agendamento foi criado apenas para diferenciar de 0 e registrar
      $element["datetime_schedule"] = date("d/m/Y H:i:s", $element["message_created"]);
      $element["datetime"] = date("d/m/Y H:i:s", $element["schedule_message"]);
      $element['message_type_detail'] = !$decrypt ? $element['message_type_detail'] : $decrypt;
      $arr[] = $element;
    }

    $message = [ 'success' => true, 'messages' => $arr ];

    return $message;
  }

  public function sendScheduleMessage($id, $userId) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');

    $stmt = $pdo->prepare("
      UPDATE clients_messages as cm
      INNER JOIN company_invitations ci ON ci.invitations_company_id = cm.company_id AND ci.invitations_employee_id = ?
      SET cm.message_created = UNIX_TIMESTAMP(),
      cm.schedule_SentEarly = ?
      WHERE cm.message_id = ?
    ");
    $stmt->execute([$userId, $userId, $id]);

    $message["success"] = true;

    return $message;
  }

  public function deleteScheduleMessage($id, $userId) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');

    $stmt = $pdo->prepare("
      UPDATE clients_messages as cm
      INNER JOIN company_invitations ci ON ci.invitations_company_id = cm.company_id AND ci.invitations_employee_id = ?
      SET cm.message_deleted_date = UNIX_TIMESTAMP(),
      cm.schedule_DeletedBy = ?
      WHERE cm.message_id = ?
    ");
    $stmt->execute([$userId, $userId, $id]);

    $message["success"] = true;

    return $message;
  }

  private function fixBrazilianPhone($phone) {
    if (substr($phone, 0, 2) === "55") { // Verifica se o número começa com "55" do brasil
        $tamanho = strlen($phone);
        if ($tamanho === 12) { // Verifica se o número possui 12 caracteres
            $ddd = substr($phone, 0, 4); // Obtém os 4 primeiros dígitos como DDD
            $numero = substr($phone, 4); // Obtém o número sem DDI e DDD.
            $quintoDigito = substr($phone, 4, 1); // Obtém o quinto dígito

            // Apenas celular começa com 97, 98, 99. Fixos não têm 9 extra.
            if ($quintoDigito >= 7 && $quintoDigito <= 9) { // Verifica se o quinto dígito é 7, 8 ou 9
                $phone = $ddd . "9" . $numero; // Adiciona um "9" antes do restante do número
            }
        }
    }
    return $phone;
}

  public function newChat($companyId, $name, $phone, $country, $setor, $userId, $device_id) {
    $message = [ 'success' => false ];

    if (
        $companyId &&
        $name !== "" && 
        $phone !== "" && 
        $country !== "" && 
        $setor !== "" &&
        $device_id !== ""
      ) {
      $pdo = $this->container->get('db');
      $client_phone = $this->fixBrazilianPhone($country . $phone);

      $message['phone'] = $client_phone;

      $mensagem_log = "";

      $stmt = $pdo->query("
        SELECT employee_name
        FROM employee_details
        WHERE employee_id = '$userId'
      ");
      $employee = $stmt->fetch();
      $usuario = $employee['employee_name'];

      $stmt = $pdo->query("
        SELECT *, count(*) as c FROM clients_registered_details
        WHERE client_phone = '$client_phone'
        AND company_id = '$companyId'
        AND company_id IN (
          SELECT invitations_company_id
          FROM company_invitations 
          WHERE invitations_employee_id = '$userId'
          AND invitations_accept > 0 
          AND invitations_finish = 0
        )
      ");
      $client = $stmt->fetch();

      if ($client['c'] == 0) {
        $stmt = $pdo->prepare("
          INSERT INTO clients_registered_details (client_phone, client_name, company_id, device_id) 
          VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$client_phone, $name, $companyId, $device_id]);
        $newChatId = $pdo->lastInsertId();
        $client_id = $newChatId;

        $mensagem_log = "Cliente Cadastrado por $usuario.";
      }
      else {
        $client_id = $client['client_id'];

        $mensagem_log = "$usuario tentou cadastrar este cliente novamente.";
      }

      $stmt = $pdo->query("
        SELECT *, count(*) as c
        FROM clients_chats_opened cco
        WHERE cco.chat_date_close = 0
        AND cco.chat_date_start > 0
        AND cco.company_id = '$companyId'
        AND cco.client_phone = '$client_phone'
        ORDER BY cco.chat_id DESC
        LIMIT 1
      ");
      $lastChat = $stmt->fetch();
      $lastChatId = $lastChat["chat_id"];

      if ($lastChat['c'] > 0) {
        $message['clientId'] = $client_id;
      }
      else {
        $stmt = $pdo->prepare("
          INSERT INTO clients_chats_opened (who_start, company_id, device_id, client_id, client_phone, chat_date_start, chat_department_id, chat_employee_id, ura_status) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute(["1", $companyId, $device_id, $client_id, $client_phone, time(), $setor, $userId, "1"]);
        $message['clientId'] = $client_id;
        $lastChatId = $pdo->lastInsertId();
      }

      $security = new CryptContent($companyId);
      $mensagem_log = $security->encrypt($mensagem_log);

      $stmt = $pdo->prepare("
        INSERT INTO clients_messages (chat_id, client_id, client_phone, company_id, department_id, who_sent, message_status, message_status_time, message_created, message_type_detail, message_device_id, system_log) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([$lastChatId, $client_id, $client_phone, $companyId, $setor, $userId, 'SENT-DEVICE', time(), time(), $mensagem_log, $device_id, '1']);

      $message["success"] = true;
    }

    return $message;
  }

  private function CloseLastStatus($chat_id) {
    $pdo = $this->container->get('db');

    $stmt = $pdo->prepare("
      UPDATE clients_chats_status 
      SET status_date_finished = UNIX_TIMESTAMP(),
      status_finished_reason = '2'
      WHERE status_date_finished = 0
      AND status_chat_id = ?
    ");
    
    $stmt->execute([$chat_id]);
  }

  public function updateChatStatus($chatId, $userId, $status, $abort, $statusLabel, $statusDateValidity, $endMessage, $telefone, $companyId, $deviceId) {
    $message = [ 'success' => false ];

    $this->CloseLastStatus($chatId);

    $pdo = $this->container->get('db');

    $stmt = $pdo->prepare("
      INSERT INTO clients_chats_status (status_date_created, status_abortIFmessageReceived, status_IDuser_created, status_type, status_label, status_date_validity, status_message_toEndChat, status_message_destiny, status_chat_id, status_company_id, status_device_id) 
      VALUES (UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$abort, $userId, $status, $statusLabel, $statusDateValidity, $endMessage, $telefone, $chatId, $companyId, $deviceId]);


    return $message;
  }

  public function getLastChat($id, $companyId, $userId) {
    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
        SELECT *
        FROM clients_chats_opened cco
        INNER JOIN clients_registered_details crd ON crd.client_id = cco.client_id
        INNER JOIN company_invitations ci ON ci.invitations_company_id = crd.company_id AND ci.invitations_employee_id = '$userId'
        WHERE cco.chat_date_close = 0
        AND crd.client_id = '$id'
        AND ci.invitations_company_id = '$companyId'
        AND ci.invitations_employee_id = '$userId'
        ORDER BY cco.chat_id DESC
        LIMIT 1
      ");

    return $stmt->fetch();
  }

  public function updateMarkUnread($chatId, $datetime) {
    $pdo = $this->container->get('db');
    $stmt = $pdo->prepare("
      UPDATE clients_chats_opened 
      SET chat_mark_unread = ?
      WHERE chat_id = ?
    ");
    $stmt->execute([$datetime, $chatId]);
  }

  public function markAsRead($id, $companyId, $userId) {
    $message = [ 'success' => false ];

    $userRepository = new UserRepository($this->container);                                                                                
    $usuario = $userRepository->getUserNameById($userId);

    $lastChat = $this->getLastChat($id, $companyId, $userId);
    $this->insertSystemLogByChatId($lastChat["chat_id"], "$usuario marcou como lido.", $userId);
    $this->read($id, $userId, $companyId, false);
    $this->updateMarkUnread($lastChat["chat_id"], 0);

    $message = [ 'success' => true ];

    return $message;
  }

  public function markAsUnread($id, $companyId, $userId) {
    $message = [ 'success' => false ];

    $userRepository = new UserRepository($this->container);                                                                                
    $usuario = $userRepository->getUserNameById($userId);

    $lastChat = $this->getLastChat($id, $companyId, $userId);
    $this->updateMarkUnread($lastChat["chat_id"], time());
    $this->insertSystemLogByChatId($lastChat["chat_id"], "$usuario marcou como não lido.", $userId);

    $message = [ 'success' => true ];

    return $message;
  }
}