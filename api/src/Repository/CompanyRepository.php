<?php
declare(strict_types=1);
namespace App\Repository;

use Pimple\Psr11\Container;
use App\Lib\Chat;
use App\Lib\Encrypt;
use Slim\Psr7\UploadedFile;

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
            SELECT permissions_employee_id
            FROM employee_permissions
            WHERE permissions_department_id IN (
                SELECT permissions_department_id
                FROM employee_permissions
                WHERE permissions_type > 0
                AND permissions_company_id = '$companyId'
                AND permissions_employee_id = '$userId'
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
            SELECT permissions_department_id
            FROM employee_permissions 
            WHERE permissions_employee_id = '$userId'
            AND permissions_company_id = '$companyId'
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
            IFNULL(ctg.group_name, 'Tags') as group_name
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
          c.company_create as created_at
        FROM company_details c 
        INNER JOIN company_invitations ci ON ci.invitations_company_id = c.company_id
        WHERE ci.invitations_employee_id = '$userId'
        AND ci.invitations_accept > 0
        AND ci.invitations_finish = 0
        AND c.company_id = '$companyId'
    ");
    $element = $stmt->fetch();

    $element["id"] = Encrypt::encode($element["id"]);
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
}