<?php
try {
    include("openAI/conexao.php");

    // Defina as variáveis para os parâmetros
    $dataStart = 0;  // Substitua pelo valor de data de início (em Unix)
    $dataEnd = 99999999999;  // Substitua pelo valor de data final (em Unix)
    $user = 2;  // Substitua pelo ID do usuário ou 0
    $companyID = 12;

    // Preparando a consulta única com subconsultas e joins
    $sql = "
    SELECT 
        COUNT(DISTINCT CASE WHEN c.company_id = :companyID AND c.who_start = 0 
                   AND c.chat_date_start >= :dataStart AND c.chat_date_start <= :dataEnd 
                   AND c.chat_date_close <= 0 
                   AND ((:user > 0 AND c.chat_employee_id = :user) OR (:user <= 0 AND c.chat_employee_id >= 0)) 
                   THEN c.chat_id END) AS NewClientChats_Active,
                   
        COUNT(DISTINCT CASE WHEN c.company_id = :companyID AND c.who_start = 0 
                   AND c.chat_date_start >= :dataStart AND c.chat_date_start <= :dataEnd 
                   AND c.chat_date_close > 0 
                   AND ((:user > 0 AND c.chat_employee_id = :user) OR (:user <= 0 AND c.chat_employee_id >= 0)) 
                   THEN c.chat_id END) AS NewClientChats_Closed,
                   
        COUNT(DISTINCT CASE WHEN c.company_id = :companyID AND who_start > 0
                   AND ((:user > 0 AND c.chat_employee_id = :user) OR (:user <= 0 AND c.chat_employee_id >= 0)) 
                   AND c.chat_date_start >= :dataStart AND c.chat_date_start <= :dataEnd 
                   AND c.chat_date_close <= 0 
                   THEN c.chat_id END) AS NewCompanyChats_Active,
                   
        COUNT(DISTINCT CASE WHEN c.company_id = :companyID AND who_start > 0
                   AND ((:user > 0 AND c.chat_employee_id = :user) OR (:user <= 0 AND c.chat_employee_id >= 0)) 
                   AND c.chat_date_start >= :dataStart AND c.chat_date_start <= :dataEnd 
                   AND c.chat_date_close > 0 
                   THEN c.chat_id END) AS NewCompanyChats_Closed,
                   
        COUNT(DISTINCT CASE WHEN m.company_id = :companyID AND m.message_created >= :dataStart 
                   AND m.message_created <= :dataEnd 
                   AND ((:user > 0 AND m.who_sent = :user) OR (:user <= 0 AND m.who_sent >= 0))
                   THEN m.message_id END) AS SentMessages,
        COUNT(DISTINCT CASE 
                    WHEN m.company_id = :companyID 
                    AND m.message_created >= :dataStart 
                    AND m.message_created <= :dataEnd 
                    AND m.who_sent <= 0 
                    AND EXISTS (
                        SELECT 1 
                        FROM clients_chats_opened c 
                        WHERE c.chat_id = m.chat_id 
                        AND c.company_id = :companyID
                        AND ((:user > 0 AND c.chat_employee_id = :user) OR (:user <= 0 AND c.chat_employee_id >= 0))
                    )
                    THEN m.message_id 
                END) AS ReceivedMessages
    FROM 
        clients_chats_opened c
    LEFT JOIN 
        clients_messages m ON c.company_id = m.company_id AND c.chat_id = m.chat_id
    WHERE 
        c.company_id = :companyID
";

    // Preparar e executar a consulta
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':dataStart', $dataStart, PDO::PARAM_INT);
    $stmt->bindParam(':dataEnd', $dataEnd, PDO::PARAM_INT);
    $stmt->bindParam(':user', $user, PDO::PARAM_INT);
    $stmt->bindParam(':companyID', $companyID, PDO::PARAM_INT);
    $stmt->execute();

    // Obter o resultado
    $result = $stmt->fetch(PDO::FETCH_ASSOC); // Use PDO::FETCH_ASSOC para um array associativo

    // Calcular os totais
    $result['NewActiveChats_Total'] = $result['NewClientChats_Active'] + $result['NewCompanyChats_Active'];
    $result['NewClosedChats_Total'] = $result['NewClientChats_Closed'] + $result['NewCompanyChats_Closed'];
    $result['AllNewChats_Total'] = $result['NewActiveChats_Total'] + $result['NewClosedChats_Total'];

    // Exibindo o resultado
    foreach ($result as $key => $value) {
        echo $key . ": " . $value . "<br>";
    }

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
