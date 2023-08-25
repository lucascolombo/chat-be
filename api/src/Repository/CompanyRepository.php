<?php
declare(strict_types=1);
namespace App\Repository;

use Pimple\Psr11\Container;
use App\Lib\Chat;
use App\Lib\Encrypt;

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
}