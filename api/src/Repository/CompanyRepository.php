<?php
declare(strict_types=1);
namespace App\Repository;

use Pimple\Psr11\Container;
use App\Lib\Chat;
use App\Lib\Encrypt;
use Slim\Psr7\UploadedFile;
use App\Lib\CryptContent;

final class CompanyRepository
{
  private $container;

  public function __construct(Container $container) {   
      $this->container= $container;
  }

  public function getCompanyFiltersOptions($userId, $companyId) {
    $message = ['success' => false];

    $message['tags'] = $this->getCompanyTags($userId, $companyId);
    $message['departments'] = $this->getCompanyDepartments($userId, $companyId);
    $message['users'] = $this->getCompanyUsers($userId, $companyId);

    $message['success'] = true;

    return $message;
  }

  private function getCompanyUsers($userId, $companyId) {
    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
        SELECT employee_id, employee_name
        FROM employee_details
        WHERE employee_id = '$userId'

        UNION

        SELECT employee_id, employee_name
        FROM employee_details
        WHERE employee_id <> '$userId'
        AND employee_id IN (
            SELECT employee_departments_employeeID
            FROM employee_departments
            WHERE employee_departments_departmentID IN (
                SELECT employee_departments_departmentID
                FROM employee_departments
                WHERE employee_departments_finishDate = 0
                AND employee_departments_companyID = '$companyId'
                AND employee_departments_employeeID = '$userId'
            )
        )
    ");
    $fetch = $stmt->fetchAll();
    $arr = [];

    foreach ($fetch as $single) {
      $element = [];
      $element["value"] = $single["employee_id"];
      $element["name"] = $single["employee_name"];
      $arr[] = $element;
    }

    return $arr;
  }

  private function getCompanyDepartments($userId, $companyId) {
    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
        SELECT cd.departments_id, cd. departments_name
        FROM company_departments cd
        WHERE cd.departments_id IN (
            SELECT employee_departments_departmentID
            FROM employee_departments 
            WHERE employee_departments_employeeID = '$userId'
            AND employee_departments_companyID = '$companyId'
        )
        ORDER BY cd.departments_orderby, cd.departments_name
    ");
    $fetch = $stmt->fetchAll();
    $arr = [];

    foreach ($fetch as $single) {
      $element = [];
      $element["value"] = $single["departments_id"];
      $element["name"] = $single["departments_name"];
      $arr[] = $element;
    }

    return $arr;
  }

  private function getCompanyTags($userId, $companyId) {
    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
        SELECT ct.id,
            ct.tag_name,
            IFNULL(ctg.group_name, 'Tags') as group_name,
            IFNULL(ctg.group_type, 0) as group_type
        FROM company_tagList ct
        LEFT JOIN company_tagGroup ctg ON ctg.group_id = ct.group_id
        WHERE ct.company_id = '$companyId'
        AND ct.company_id IN (
            SELECT invitations_company_id
            FROM company_invitations 
            WHERE invitations_employee_id = '$userId'
            AND invitations_accept > 0 
            AND invitations_finish = 0
        )
        ORDER BY ctg.group_orderby, ct.tag_orderby, ct.tag_name
    ");
    $fetch = $stmt->fetchAll();
    $arr = [];

    foreach ($fetch as $single) {
      $element = [];
      $element["value"] = $single["id"];
      $element["name"] = $single["group_name"] . " > " . $single["tag_name"];
      $element["group_type"] = $single["group_type"];
      $arr[] = $element;
    }

    return $arr;
  }

  public function getUserCompanies($userId) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
        SELECT 
          c.company_id as id, 
          c.company_name as name 
        FROM company_details c 
        INNER JOIN company_invitations ci ON ci.invitations_company_id = c.company_id
        WHERE ci.invitations_employee_id = '$userId'
        AND ci.invitations_accept > 0
        AND ci.invitations_finish = 0
    ");
    $fetch = $stmt->fetchAll();
    $arr = [];

    foreach ($fetch as $single) {
      $element = $single;
      $element["id"] = Encrypt::encode($element["id"]);
      $arr[] = $element;
    }

    $message = [ 'success' => true, 'companies' => $arr ];

    return $message;
  }

  public function getAllCompanyUsers($userId, $companyId) {
    $message = [ 'success' => false, 'users' => [] ];

    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
      SELECT e.employee_id, e.employee_name 
      FROM employee_details e
      WHERE e.employee_id IN (
        SELECT ci.invitations_employee_id
        FROM company_invitations ci
        WHERE ci.invitations_company_id = '$companyId'
        AND ci.invitations_accept > 0
        AND ci.invitations_finish = 0
      )
      AND e.employee_id <> '$userId'
    ");
    $fetch = $stmt->fetchAll();
    $arr = [];

    foreach ($fetch as $single) {
      $element = [];
      $element["value"] = $single["employee_id"];
      $element["name"] = $single["employee_name"];
      $arr[] = $element;
    }

    $message = [ 'success' => true, 'users' => $arr ];

    return $message;
  }

  public function getCompanyData($userId, $companyId) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
        SELECT 
          c.company_id as id, 
          c.company_name as name,
          c.company_mail as email,
          c.company_tel as phone,
          c.company_manager as manager,
          c.company_OpenIA_Key as openAIKey,
          c.company_create as created_at
        FROM company_details c 
        INNER JOIN company_invitations ci ON ci.invitations_company_id = c.company_id
        WHERE ci.invitations_employee_id = '$userId'
        AND ci.invitations_accept > 0
        AND ci.invitations_finish = 0
        AND c.company_id = '$companyId'
    ");
    $element = $stmt->fetch();

    $security = new CryptContent($companyId);
    $element["id"] = Encrypt::encode($element["id"]);
    $element["openAIKey"] = $security->decrypt($element['openAIKey']);
    $element["created_at"] = date("d/m/Y H:i:s", $element["created_at"]);

    $message = [ 'success' => true, 'company' => $element ];

    return $message;
  }

  private function moveUploadedFile($directory, UploadedFile $uploadedFile, $basename, $extension) {
    $filename = $basename . "." . $extension;

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
  }

  public function uploadFile($userId, $companyId, $files) {
    $dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/company_' . $companyId;

    $message = [ 'success' => false, "file" => [] ];

    if (!file_exists($dir)) {
      mkdir($dir, 0777, true);
    }

    if ($companyId !== null) {
      $pdo = $this->container->get('db');
      $datetime = time();

      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();
      if ($permission) {
        $index = 0;

        foreach($files["files"] as $file) {
          if ($file->getError() === UPLOAD_ERR_OK) {
            if ($file->getSize() <= 25 * 1024 * 1024) {
              $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);
              $basename =  time() + $userId;
              $filename = $this->moveUploadedFile($dir, $file, $basename, $extension);
              $filePath = 'https://' . $_SERVER['HTTP_HOST'] . '/file/company_' . $companyId . '/' . $filename;

              $message["file"]["path"] = $filePath;
              $message["file"]["size"] = $file->getSize();

              $index++;
            }
          }
        }
      }

      $message["success"] = true;
    }

    return $message;
  }

  public function deleteFile($userId, $companyId, $fileId, $deleteAll) {
    $message = [ 'success' => false ];

    if ($companyId !== null) {
      $pdo = $this->container->get('db');

      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();
      if ($permission) {
        $deleteVars = [time(), $userId, $companyId];
        $deleteQuery = "
          UPDATE company_fixed_files
          SET FixedFiles_deletedDate = ?, FixedFiles_deletedBy = ?
          WHERE FixedFiles_CompanyID = ?";

        if ($deleteAll == 0) {
          $deleteQuery .= " AND FixedFiles_id = ?";
          $deleteVars[] = $fileId;
        }
        else {
          $stmt = $pdo->query("
            SELECT FixedFiles_FileLink
            FROM company_fixed_files
            WHERE FixedFiles_id = '$fileId'
          ");
          $fileElement = $stmt->fetch();
          $link = $fileElement["FixedFiles_FileLink"];

          $deleteQuery .= " AND FixedFiles_FileLink = ?";
          $deleteVars[] = $link;
        }

        $stmt = $pdo->prepare($deleteQuery);
        $stmt->execute($deleteVars);

        $message["success"] = true;
      }
    }

    return $message;
  }

  private function insertFile(
    $pdo,
    $userId,
    $companyId,
    $file,
    $label,
    $setor,
    $tag,
    $where,
    $fileSize,
    $fileType
  ) {
    $stmt = $pdo->prepare("
      INSERT INTO company_fixed_files (
        FixedFiles_createdBy,
        FixedFiles_createdDate,
        FixedFiles_FileID,
        FixedFiles_FileLink,
        FixedFiles_FileSize,
        FixedFiles_FileType,
        FixedFiles_FileLabel,
        FixedFiles_CompanyID,
        FixedFiles_DepartmentID,
        FixedFiles_EmployeeID,
        FixedFiles_TagID
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, time(), time() + 1, $file, $fileSize, $fileType, $label, $companyId, ($where == 1) ? $setor : 0, ($where == 2) ? $userId : 0, $tag === '' ? 0 : $tag]);
  }

  public function createFile($userId, $companyId, $file, $label, $setor, $tag, $where, $fileSize, $fileType) {
    $message = [ 'success' => false ];
    
    if ($companyId !== null) {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission && $file !== '' && $label !== '') {
        if ($where == 1 && count($setor) > 0) {
          foreach ($setor as $s) {
            $this->insertFile($pdo, $userId, $companyId, $file, $label, $s, $tag, $where, $fileSize, $fileType);
          }
          $message["success"] = true;
        } else if ($where != 1) {
          $this->insertFile($pdo, $userId, $companyId, $file, $label, $setor, $tag, $where, $fileSize, $fileType);
          $message["success"] = true;
        }
      }
    }

    return $message;
  }

  public function getAllFixedFiles($userId, $companyId) {
    $message = [ 'success' => false, 'files' => [] ];
    
    if ($companyId !== null) {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission) {
        $stmt = $pdo->query("
          SELECT *, count(*) as fileCount 
          FROM company_fixed_files 
          WHERE FixedFiles_CompanyID = '$companyId'
          AND (
            FixedFiles_EmployeeID = '$userId' OR 
            FixedFiles_EmployeeID = '0'
          )
          AND FixedFiles_deletedDate = '0'
          GROUP BY FixedFiles_FileLink
        ");
        $fetch = $stmt->fetchAll();
        $arr = [];

        foreach ($fetch as $single) {
          $element = $single;
          $element["FixedFiles_createdDate"] = date("d/m/Y H:i", $element["FixedFiles_createdDate"]);
          $arr[] = $element;
        }

        $message = [ 'success' => true, 'files' => $arr ];
      }
    }

    return $message;
  }

  public function createDefaultMessage($userId, $companyId, $title, $message, $tag) {
    $msg = [ 'success' => false ];
    
    if ($companyId !== null) {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission && $title !== '' && $message !== '') {
        $stmt = $pdo->prepare("
          INSERT INTO company_fixed_messages (
            FixedMSG_createdBy,
            FixedMSG_createdDate,
            FixedMSG_msgID,
            FixedMSG_Label,
            FixedMSG_FullTXT,
            FixedMSG_CompanyID,
            FixedMSG_EmployeeID,
            FixedMSG_TagID
          )
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, time(), time() + 1, $title, $message, $companyId, $userId, $tag === '' ? 0 : $tag]);

        $msg = [ 'success' => true ];
      }
    }

    return $msg;
  }

  public function getAllDefaultMessages($userId, $companyId) {
    $message = [ 'success' => false, 'messages' => [] ];
    
    if ($companyId !== null) {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission) {
        $stmt = $pdo->query("
          SELECT * FROM company_fixed_messages
          WHERE FixedMSG_EmployeeID = '$userId'
          AND FixedMSG_CompanyID = '$companyId'
          AND FixedMSG_deletedDate = '0'
          ORDER BY FixedMSG_OrderBy ASC
        ");
        $fetch = $stmt->fetchAll();
        $arr = [];

        foreach ($fetch as $single) {
          $element = $single;
          $element["FixedMSG_createdDate"] = date("d/m/Y H:i", $element["FixedMSG_createdDate"]);
          $arr[] = $element;
        }

        $message = [ 'success' => true, 'messages' => $arr ];
      }
    }

    return $message;
  }

  public function updateMessage($userId, $companyId, $messageId, $title, $content) {
    $message = [ 'success' => false ];
    
    if ($companyId !== null && $messageId !== null) {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission) {
        if ($title) {
          $stmt = $pdo->prepare("
            UPDATE company_fixed_messages 
            SET FixedMSG_Label = ?
            WHERE FixedMSG_id = ?
          ");
          $stmt->execute([$title, $messageId]);
        }

        if ($content) {
          $stmt = $pdo->prepare("
            UPDATE company_fixed_messages 
            SET FixedMSG_FullTXT = ?
            WHERE FixedMSG_id = ?
          ");
          $stmt->execute([$content, $messageId]);
        }

        $message = [ 'success' => true ];
      }
    }

    return $message;
  }

  public function shareMessages($userId, $companyId, $users, $messages) {
    $message = [ 'success' => false ];
    
    if ($companyId !== null) {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission) {
        foreach ($users as $userCopyId) {
          $stmt = $pdo->query("
            SELECT *  
            FROM company_fixed_messages 
            WHERE FixedMSG_id IN ('" . implode("' , '", $messages) . "')
          ");
          $fetch = $stmt->fetchAll();
          $arr = [];

          foreach ($fetch as $single) {
            $stmt = $pdo->prepare("
              INSERT INTO company_fixed_messages (
                FixedMSG_createdBy,
                FixedMSG_createdDate,
                FixedMSG_msgID,
                FixedMSG_Label,
                FixedMSG_FullTXT,
                FixedMSG_CompanyID,
                FixedMSG_EmployeeID,
                FixedMSG_TagID
              )
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
              $userId, 
              time(), 
              time() + 1, 
              $single["FixedMSG_Label"], 
              $single["FixedMSG_FullTXT"], 
              $companyId, 
              $userCopyId, 
              $single["FixedMSG_TagID"]
            ]);
          }
        }

        $message = [ 'success' => true ];
      }
    }

    return $message;
  }

  public function deleteMessage($userId, $companyId, $messageId) {
    $message = [ 'success' => false ];
    
    if ($companyId !== null && $messageId !== null) {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission) {
        $stmt = $pdo->prepare("
          UPDATE company_fixed_messages 
          SET FixedMSG_deletedDate = ?,
          FixedMSG_deletedBy = ?
          WHERE FixedMSG_id = ?
        ");
        $stmt->execute([time(), $userId, $messageId]);

        $message = [ 'success' => true ];
      }
    }

    return $message;
  }

  public function reorderMessage($userId, $companyId, $messageId, $order) {
    $message = [ 'success' => false ];
    
    if ($companyId !== null && $messageId !== null) {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission) {
        $stmt = $pdo->prepare("
          UPDATE company_fixed_messages 
          SET FixedMSG_OrderBy = ?
          WHERE FixedMSG_id = ?
        ");
        $stmt->execute([$order, $messageId]);

        $message = [ 'success' => true ];
      }
    }

    return $message;
  }

  public function addUser($userId, $companyId, $name, $email, $phone, $password) {
    $message = [ 'success' => false, 'error' => 'Erro na criação do usuário, tente mais tarde.', 'message' => '' ];

    if ($companyId !== null) {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission) {
        $stmt = $pdo->query("
          SELECT IF(company_max_users - (SELECT COUNT(*) FROM company_invitations WHERE invitations_company_id = '$companyId' AND invitations_accept > 0 AND invitations_finish = 0) > 0, 1, 0) FROM company_details WHERE company_id = '$companyId';
        ");
        $canAddUser = $stmt->fetchColumn();

        if ($canAddUser > 0) {
          $stmt = $pdo->query("
            SELECT employee_id FROM employee_details 
            WHERE employee_mail = '$email'
          ");
          $userExists = $stmt->fetchColumn();

          $newUserId = 0;

          if ($userExists > 0) {
            $newUserId = $userExists;
            $message['error'] = '';
            $message['message'] = 'Usuário vinculado com sucesso. No entanto, não foi possível alterar a senha, pois o e-mail informado já está cadastrado no sistema.<br />Se necessário utilize a função Esqueci a Senha na tela de login.';
          }
          else {
            $stmt = $pdo->prepare("
              INSERT INTO employee_details 
              (employee_name, employee_mail, employee_password, employee_tel, created_at)
              VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone, time()]);
            $newUserId = $pdo->lastInsertId();

            $message['error'] = '';
            $message['message'] = 'Usuário cadastrado com sucesso.';
          }

          $stmt = $pdo->query("
            SELECT COUNT(*) FROM company_invitations ci
            INNER JOIN employee_details e ON e.employee_id = ci.invitations_employee_id
            WHERE e.employee_mail = '$email'
            AND ci.invitations_company_id = '$companyId'
          ");
          $userCount = $stmt->fetchColumn();

          if ($userCount == 0) {
            $stmt = $pdo->prepare("
              INSERT INTO company_invitations 
              (invitations_company_id, invitations_employee_id, invitations_employee_id_created, invitations_create, invitations_accept)
              VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$companyId, $newUserId, $userId, time(), time()]);

            $message['success'] = true;
          }
          else {
            $message = [ 'success' => false, 'error' => 'E-mail já cadastrado no sistema.', 'message' => '' ];
          }
        }
        else {
          $message = [ 'success' => false, 'error' => 'A empresa já atingiu o limite de usuários cadastrados.' ];
        }
      }
    }

    return $message;
  }

  public function getAllCompanyDepartments($userId, $companyId) {
    $message = [ 'success' => false, 'departments' => [] ];

    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
        SELECT cd.departments_id, cd.departments_name, cd.departments_device_id, cd.restrict_access
        FROM company_departments cd
        WHERE cd.departments_company_id = '$companyId'
        AND cd.departments_finish = 0
        ORDER BY cd.departments_orderby, cd.departments_name
    ");
    $fetch = $stmt->fetchAll();
    $arr = [];

    foreach ($fetch as $single) {
      $element = [];
      $element["value"] = $single["departments_id"];
      $element["name"] = $single["departments_name"];
      $element["device_id"] = $single["departments_device_id"];
      $element["restrict_access"] = $single["restrict_access"];
      $arr[] = $element;
    }

    $message = [ 'success' => true, 'departments' => $arr ];

    return $message;
  }

  public function getEmployees($userId, $companyId) {
    $message = [ 'success' => false, 'users' => [] ];

    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
      SELECT 
        e.employee_id, 
        e.employee_name, 
        e.employee_mail, 
        e.employee_tel, 
        e.created_at, 
        IF(CAST('$userId' as UNSIGNED) = e.employee_id, 1, 0) as isMe, 
        (SELECT GROUP_CONCAT(employee_departments_departmentID) FROM employee_departments WHERE employee_departments_employeeID = e.employee_id AND employee_departments_finishDate = 0 AND employee_departments_finishBy = 0) as departments,
        ceg.company_employee_configs_employee_displayname as display_name,
        ceg.company_employee_restrict_access as restrict_access,
        ceg.grammar_correction
      FROM employee_details e
      LEFT JOIN company_employee_configs ceg ON ceg.company_employee_configs_employee_id = e.employee_id AND ceg.company_employee_configs_company_id = '$companyId'
      WHERE e.employee_id IN (
        SELECT ci.invitations_employee_id
        FROM company_invitations ci
        WHERE ci.invitations_company_id = '$companyId'
        AND ci.invitations_accept > 0
        AND ci.invitations_finish = 0
      )
    ");

    $fetch = $stmt->fetchAll();
    $arr = [];

    foreach ($fetch as $single) {
      $element = $single;
      $element["created_at"] = date("d/m/Y H:i", $element["created_at"]);
      $arr[] = $element;
    }

    $message = [ 'success' => true, 'users' => $arr ];

    return $message;
  }

  public function editUser($userId, $userRepository, $companyId, $editUserId, $departments, $display_name, $activate_access, $week_hours, $grammar_correction) {
    $message = [ 'success' => false, 'error' => 'Erro na edição do usuário, tente mais tarde.', 'message' => '' ];
    
    if ($companyId !== null && $editUserId != 0 && count($departments) > 0) {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission) {        
        $stmt = $pdo->prepare("
          UPDATE employee_departments 
          SET employee_departments_finishDate = ?,
          	employee_departments_finishBy = ?
          WHERE employee_departments_employeeID = ?
        ");
        $stmt->execute([time(), $userId, $editUserId]);

        foreach ($departments as $departmentId) {
          $stmt = $pdo->prepare("
            INSERT INTO employee_departments (
              employee_departments_employeeID, 
              employee_departments_departmentID, 
              employee_departments_companyID, 
              employee_departments_createDate, 
              employee_departments_createBy
            )
            VALUES (?, ?, ?, ?, ?)
          ");
          $stmt->execute([$editUserId, $departmentId, $companyId, time(), $userId]);
        }

        $stmt = $pdo->prepare("
          UPDATE permissions_employee_access_time 
          SET permissions_employee_access_time_deletedBy = ?,
            permissions_employee_access_time_deletedAt = ?
          WHERE permissions_employee_access_time_employee_id = ? 
          AND permissions_employee_access_time_company_id = ?
        ");
        $stmt->execute([$userId, time(), $editUserId, $companyId]);

        $week = [
          "Seg" => 1,
          "Ter" => 2,
          "Qua" => 3,
          "Qui" => 4,
          "Sex" => 5,
          "Sab" => 6,
          "Dom" => 7
        ];

        $remove_restrict_access = true;

        if ($activate_access) {
          foreach($week as $w) {
            $stmt = $pdo->prepare("
              INSERT INTO permissions_employee_access_time (
                permissions_employee_access_time_employee_id, 
                permissions_employee_access_time_company_id, 
                permissions_employee_access_time_work_day,
                permissions_employee_access_time_hour_start,
                permissions_employee_access_time_hour_end,
                permissions_employee_access_time_createdBy,
                permissions_employee_access_time_createdAt
              )
              VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($week_hours[$w]["active"] && $week_hours[$w]["from"] && $week_hours[$w]["to"]) {
              $stmt->execute([$editUserId, $companyId, $w, $week_hours[$w]["from"], $week_hours[$w]["to"],  $userId, time()]);
              $remove_restrict_access = false;
            }
          }
        }
        else {
          foreach($week as $w) {
            $stmt = $pdo->prepare("
              INSERT INTO permissions_employee_access_time (
                permissions_employee_access_time_employee_id, 
                permissions_employee_access_time_company_id, 
                permissions_employee_access_time_work_day,
                permissions_employee_access_time_createdBy,
                permissions_employee_access_time_createdAt
              )
              VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([$editUserId, $companyId, $w, $userId, time()]);
          }
        }

        $grammar_correction = $grammar_correction ? 1 : 0;
        $restrict_access = $activate_access ? 1 : 0;
        if ($remove_restrict_access) $restrict_access = 0;
        $stmt = $pdo->prepare("
          INSERT INTO `company_employee_configs` 
          (`company_employee_configs_company_id`, `company_employee_configs_employee_id`, `company_employee_configs_employee_displayname`, `company_employee_restrict_access`, `grammar_correction`)
          VALUES ('$companyId', '$editUserId', '$display_name', '$restrict_access', '$grammar_correction')
          ON DUPLICATE KEY 
          UPDATE `company_employee_restrict_access` = '$restrict_access', `company_employee_configs_employee_displayname` = '$display_name', `grammar_correction` = '$grammar_correction';
        ");
        $stmt->execute();

        $userRepository->updateUserLoggedIn($userRepository->getUserById($editUserId), new \DateTimeImmutable(), 0);

        $message = [ 'success' => true, 'error' => '', 'message' => 'Usuário alterado com sucesso!' ];
      }
    }

    return $message;
  }

  public function getUserAccessTime($userId, $companyId, $employeeId) {
    $message = [ 'success' => false, 'access_time' => [] ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission) {  
      $stmt = $pdo->query("
        SELECT * 
        FROM permissions_employee_access_time 
        WHERE permissions_employee_access_time_employee_id = '$employeeId' 
        AND permissions_employee_access_time_company_id = '$companyId' 
        AND permissions_employee_access_time_deletedAt = 0
      ");
      $fetch = $stmt->fetchAll();
      $arr = [];

      foreach ($fetch as $single) {
        $element = $single;
        $arr[] = $element;
      }

      $message = [ 'success' => true, 'access_time' => $arr ];
    }

    return $message;
  }

  public function userRestrictAccess($userId, $companyId) {
    $pdo = $this->container->get('db');

    $stmt = $pdo->query("
      SELECT company_employee_restrict_access FROM company_employee_configs 
      WHERE company_employee_configs_company_id = '$companyId'
      AND company_employee_configs_employee_id = '$userId'
    ");
    $restrictAccess = $stmt->fetch();

    $value = $restrictAccess ? $restrictAccess['company_employee_restrict_access'] : 0;

    return $value > 0;
  }

  public function getEmployeeAccessTime($userId, $companyId) {
    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission) {
      $w = date('w');
      $w = $w == 0 ? 7 : $w;
      $now = date('H:i:s');

      $stmt = $pdo->query("
        SELECT `permissions_employee_access_time_hour_end` as end 
        FROM `permissions_employee_access_time` 
        WHERE `permissions_employee_access_time_employee_id` = $userId 
        AND `permissions_employee_access_time_company_id` = $companyId 
        AND `permissions_employee_access_time_work_day` = $w 
        AND `permissions_employee_access_time_hour_start` <= NOW() 
        AND `permissions_employee_access_time_hour_end` >= NOW() 
        AND `permissions_employee_access_time_deletedAt` = 0
      ");
      $time = $stmt->fetch();

      return $time ? $time['end'] : null;
    }

    return null;
  }
  
  private function saveURATree($menu, $companyId, $fatherId, $device_id, &$btns2Fix) {
    $pdo = $this->container->get('db');

    $order = $menu["id"];
    $footer = $menu["footer"];
    $message_text = $menu["content"];
    $title = $menu["header"];
    $ura_name = $menu["name"];

    $stmt = $pdo->prepare("
      INSERT INTO company_URA (
        company_id, 
        device_id, 
        URA_name,
        message_text,
        title,
        footer,
        URA_order,
        father_id,
        created_at
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$companyId, $device_id, $ura_name, $message_text, $title, $footer, $order, $fatherId, time()]);
    $newFatherId = $pdo->lastInsertId();
    $btns2Fix[$menu["id"]] = $newFatherId;
      
    if (count($menu["buttons"]) > 0) {
      foreach($menu["buttons"] as $button) {
        $label = $button["name"];
        $type = $button["type"] . "";
        $idObject = $button["idRelated"] . "";

        $stmt = $pdo->prepare("
          INSERT INTO company_URA_options (
            company_URA_id, 
            company_URA_label, 
            company_URA_options_type,
            company_URA_options_id_object
          )
          VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$newFatherId, $label, $type, $idObject]);
      }
    }

    if (count($menu["children"]) > 0) {
      foreach($menu["children"] as $child) {
        $this->saveURATree($child, $companyId, $newFatherId, $device_id, $btns2Fix);
      }
    }
  }

  public function saveURA($userId, $companyId, $menus, $device_id) {
    $message = [ 'success' => false ];

    if (count($menus) > 0 && $device_id !== "") {
      $pdo = $this->container->get('db');
      $stmt = $pdo->query("
        SELECT * FROM company_invitations 
        WHERE invitations_company_id = '$companyId'
        AND invitations_employee_id = '$userId'
        AND invitations_accept > 0 
        AND invitations_finish = 0
      ");
      $permission = $stmt->fetch();

      if ($permission) {  
        $stmt = $pdo->prepare("
          UPDATE company_URA 
          SET deleted = ?
          WHERE company_id = ? 
          AND device_id = ?
        ");
        $stmt->execute([time(), $companyId, $device_id]);

        $btns2Fix = [];
        $this->saveURATree($menus[0], $companyId, 0, $device_id, $btns2Fix);

        // fix all arrays in button related to menus
        $stmt = $pdo->query("
          SELECT opt.* FROM company_URA_options opt
          INNER JOIN company_URA ura ON ura.id = opt.company_URA_id
          WHERE ura.company_id = '$companyId'
          AND ura.device_id = '$device_id'
          AND ura.deleted = 0
          AND opt.company_URA_options_type = '3'
        ");
        $buttons = $stmt->fetchAll();
        foreach ($buttons as $button) {
          $id = $button['company_URA_options_id'];
          $key = $button['company_URA_options_id_object'];

          if (isset($btns2Fix[$key])) {
            $stmt = $pdo->prepare("
              UPDATE company_URA_options 
              SET company_URA_options_id_object = ?
              WHERE company_URA_options_id = ? 
            ");
            $stmt->execute([$btns2Fix[$key], $id]);
          }
        }
        // end fix all arrays in button related to menus

        $message = [ 'success' => true, 'buttons' => $btns2Fix ];
      }
    }

    return $message;
  }

  private function buildURA($fatherId, $menus, $buttons) {
    $tree = [];

    foreach ($menus as $id => $ura) {
        if ($ura['father_id'] == $fatherId) {
            $tree[] = [
              'id' => $ura['id'],
              'name' => $ura['URA_name'],
              'content' => $ura['message_text'],
              'header' => $ura['title'],
              'footer' => $ura['footer'],
              'children' => $this->buildURA($id, $menus, $buttons),
              'buttons' => $buttons[$id] ?? []
            ];
        }
    }

    return $tree;
  }

  public function getURA($userId, $companyId, $device_id) {
    $message = [ 'success' => false, 'menus' => [] ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission) {
      $allMenus = [];
      $allButtons = [];

      $stmt = $pdo->query("
        SELECT * FROM company_URA 
        WHERE company_id = '$companyId'
        AND device_id = '$device_id'
        AND deleted = 0 
        ORDER BY URA_order ASC
      ");
      $menus = $stmt->fetchAll();
      foreach ($menus as $menu) {
        $allMenus[$menu["id"]] = $menu;
      }

      $stmt = $pdo->query("
        SELECT opt.* FROM company_URA_options opt
        INNER JOIN company_URA ura ON ura.id = opt.company_URA_id
        WHERE ura.company_id = '$companyId'
        AND ura.device_id = '$device_id'
        AND ura.deleted = 0
        ORDER BY opt.company_URA_options_id
      ");
      $buttons = $stmt->fetchAll();
      foreach ($buttons as $k => $button) {
        $allButtons[$button["company_URA_id"]][] = [
          "id" => $k,
          "name" => $button["company_URA_label"],
          "type" => $button["company_URA_options_type"],
          "idRelated" => $button["company_URA_options_id_object"]
        ];
      }

      $message = [ 'success' => true, 'menus' => $this->buildURA(0, $allMenus, $allButtons), 'buttons' => $allButtons ];
    }

    return $message;
  }

  public function getAllDevices($companyId, $userId) {
    $message = [ 'success' => false, 'devices' => [] ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission) {
      $devices = [];

      $stmt = $pdo->query("
        SELECT * FROM company_devices 
        WHERE device_company_id = '$companyId'
        AND device_status != 2
      ");
      $fetch = $stmt->fetchAll();

      foreach ($fetch as $single) {
        $devices[] = [
          'value' => $single["device_id"], 
          'name' => $single["device_detail"], 
          'status' => $single["device_status"]
        ];
      }

      $message = [ 'success' => true, 'devices' => $devices ];
    }

    return $message;
  }

  public function updateDepartment($companyId, $userId, $department, $activate_access, $week_hours) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission) {
      if ($department['action'] === 'create' &&  $department['name'] !== '' &&  $department['device_id'] !== '') {
        $stmt = $pdo->prepare("
          INSERT INTO company_departments (
            departments_name, 
            departments_company_id,
            departments_device_id,
            departments_create
          )
          VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$department['name'], $companyId, $department['device_id'], time()]);

        $message = [ 'success' => true ];
      }
      else if ($department['action'] === 'update' && $department['id'] !== '' && $department['name'] !== '' && $department['device_id']) {
        $restrict_access = $activate_access ? 1 : 0;

        $stmt = $pdo->prepare("
          UPDATE company_departments SET 
          departments_name = ?,
          departments_device_id = ?,
          restrict_access = ?
          WHERE departments_id = ?
        ");
        $stmt->execute([$department['name'], $department['device_id'], $restrict_access, $department['id']]);

        $stmt = $pdo->prepare("
          UPDATE permissions_department_access_time 
          SET access_time_deletedBy = ?,
            access_time_deletedAt = ?
          WHERE access_time_department_id = ? 
          AND access_time_company_id = ?
        ");
        $stmt->execute([$userId, time(), $department['id'], $companyId]);

        $week = [
          "Seg" => 1,
          "Ter" => 2,
          "Qua" => 3,
          "Qui" => 4,
          "Sex" => 5,
          "Sab" => 6,
          "Dom" => 7
        ];

        $remove_restrict_access = true;

        if ($activate_access) {
          foreach($week as $w) {
            $stmt = $pdo->prepare("
              INSERT INTO permissions_department_access_time (
                access_time_department_id, 
                access_time_company_id, 
                access_time_work_day,
                access_time_hour_start,
                access_time_hour_end,
                access_time_createdBy,
                access_time_createdAt
              )
              VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($week_hours[$w]["active"] && $week_hours[$w]["from"] && $week_hours[$w]["to"]) {
              $stmt->execute([$department['id'], $companyId, $w, $week_hours[$w]["from"], $week_hours[$w]["to"],  $userId, time()]);
              $remove_restrict_access = false;
            }
          }
        }
        else {
          foreach($week as $w) {
            $stmt = $pdo->prepare("
              INSERT INTO permissions_department_access_time (
                access_time_department_id, 
                access_time_company_id, 
                access_time_work_day,
                access_time_createdBy,
                access_time_createdAt
              )
              VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([$department['id'], $companyId, $w, $userId, time()]);
          }
        }

        $restrict_access = $activate_access ? 1 : 0;
        if ($remove_restrict_access) $restrict_access = 0;

        $message = [ 'success' => true ];
      }
    }

    return $message;
  }

  public function deleteDepartment($companyId, $userId, $departmentId) {
    $message = [ 'success' => false, 'message' => '' ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission) {
      $stmt = $pdo->query("
        SELECT uraOpt.* FROM company_URA_options  uraOpt
        INNER JOIN company_URA ura ON ura.id = uraOpt.company_URA_id
        WHERE uraOpt.company_URA_options_type = '2'
        AND uraOpt.company_URA_options_id_object = '$departmentId'
        AND ura.deleted = 0
        AND ura.company_id = '$companyId'
      ");
      $fetch = $stmt->fetchAll();

      if (count($fetch) > 0) {
        $message = [ 'success' => false, 'message' => 'Não é possível remover este departamento pois ele faz parte de uma URA ativa.' ];
      }
      else {
        $stmt = $pdo->prepare("
          UPDATE company_departments 
          SET departments_finish = ?
          WHERE departments_id = ?
        ");
        $stmt->execute([time(), $departmentId]);

        $stmt = $pdo->prepare("
          UPDATE employee_departments 
          SET employee_departments_finishDate = ?,
            employee_departments_finishBy = ?
          WHERE employee_departments_departmentID = ?
        ");
        $stmt->execute([time(), $userId, $departmentId]);

        $message = [ 'success' => true, 'message' => '' ];
      }
    }

    return $message;
  }

  public function getAllCompanyTags($companyId, $userId) {
    $message = [ 'success' => false, 'groups' => [] ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission) {
      $stmt = $pdo->query("
        SELECT * FROM company_tagGroup
        WHERE company_id = '$companyId'
        AND group_finish = '0'
        ORDER BY group_orderby ASC
      ");
      $fetch = $stmt->fetchAll();
      $arr = [];

      foreach ($fetch as $single) {
        $groupId = $single["group_id"];
        $children = [];

        $stmtTags = $pdo->query("
          SELECT * FROM company_tagList
          WHERE company_id = '$companyId'
          AND group_id = '$groupId'
          AND tag_finish = '0'
          ORDER BY tag_orderby ASC
        ");
        $fetchTags = $stmtTags->fetchAll();
        foreach ($fetchTags as $tag) {
          $children[] = [
            "id" => $tag["tag_orderby"],
            "tagId" => $tag["id"],
            "name" => $tag["tag_name"],
          ];
        }

        $arr[] = [
          "id" => $single["group_orderby"],
          "groupId" => $groupId,
          "name" => $single["group_name"],
          "funil" => $single["group_type"],
          "children" => $children
        ];
      }

      $message["success"] = true;
      $message["groups"] = $arr;
    }

    return $message;
  }

  public function saveCompanyTags($companyId, $userId, $groups) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission) {
      foreach ($groups as $key => $single) {
        $groupId = $single["groupId"];
        $order = $key;
        $name = $single["name"];
        $funil = $single["funil"];
        $children = $single["children"];
        
        if ($groupId == 0) {
          $stmt = $pdo->prepare("
            INSERT INTO company_tagGroup 
            (company_id, group_name, group_type, group_orderby)
            VALUES (?, ?, ?, ?)
          ");
          $stmt->execute([$companyId, $name, $funil, $order]);
          $groupId = $pdo->lastInsertId();
        }
        else {
          $stmt = $pdo->prepare("
            UPDATE company_tagGroup 
            SET group_name = ?,
            group_type = ?,
            group_orderby = ?
            WHERE group_id = ?
          ");
          $stmt->execute([$name, $funil, $order, $groupId]);
        }

        foreach ($children as $keyTag => $tag) {
          $tagId = $tag["tagId"];
          $tagOrder = $keyTag;
          $tagName = $tag["name"];

          if ($tagId == 0) {
            $stmt = $pdo->prepare("
              INSERT INTO company_tagList 
              (company_id, group_id, tag_name, tag_orderby)
              VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$companyId, $groupId, $tagName, $tagOrder]);
          }
          else {
            $stmt = $pdo->prepare("
              UPDATE company_tagList 
              SET tag_name = ?,
              tag_orderby = ?
              WHERE id = ?
            ");
            $stmt->execute([$tagName, $tagOrder, $tagId]);
          }
        }
      }

      $message["success"] = true;
    }

    return $message;
  }

  public function deleteTagGroup($companyId, $userId, $groupId) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission && $groupId > 0) {
      $stmt = $pdo->prepare("
        UPDATE company_tagGroup 
        SET group_finish = ?
        WHERE group_id = ?
        AND company_id = ?
      ");
      $stmt->execute([time(), $groupId, $companyId]);

      $stmt = $pdo->prepare("
        UPDATE company_tagList 
        SET tag_finish = ?
        WHERE group_id = ?
        AND company_id = ?
      ");
      $stmt->execute([time(), $groupId, $companyId]);

      $message["success"] = true;
    }

    return $message;
  }

  public function deleteTag($companyId, $userId, $tagId) {
    $message = [ 'success' => false ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission && $tagId > 0) {
      $stmt = $pdo->prepare("
        UPDATE company_tagList 
        SET tag_finish = ?
        WHERE id = ?
        AND company_id = ?
      ");
      $stmt->execute([time(), $tagId, $companyId]);

      $message["success"] = true;
    }

    return $message;
  }

  public function getDepartmentAccessTime($userId, $companyId, $departmentId) {
    $message = [ 'success' => false, 'access_time' => [] ];

    $pdo = $this->container->get('db');
    $stmt = $pdo->query("
      SELECT * FROM company_invitations 
      WHERE invitations_company_id = '$companyId'
      AND invitations_employee_id = '$userId'
      AND invitations_accept > 0 
      AND invitations_finish = 0
    ");
    $permission = $stmt->fetch();

    if ($permission) {  
      $stmt = $pdo->query("
        SELECT * 
        FROM permissions_department_access_time 
        WHERE access_time_department_id = '$departmentId' 
        AND access_time_company_id = '$companyId' 
        AND access_time_deletedAt = 0
      ");
      $fetch = $stmt->fetchAll();
      $arr = [];

      foreach ($fetch as $single) {
        $element = $single;
        $arr[] = $element;
      }

      $message = [ 'success' => true, 'access_time' => $arr ];
    }

    return $message;
  }
}