<?php
$instancia = "3B96A12900AD60380F14C24260D5D464";
$token = "93D0BDF2569307F20D1B4E0E";
//Esse token acima dá pra usar para testar, sem problemas, é um isolado peguei para usarmos.

//GRV
//$instancia = "3ADF9B3B1F73E07082C38AC416EF6803";
//$token = "2C279754770F2B553F13220E";

// Usando a classe
//$actionButtons = new ActionButtons($instancia,$token,$phone,$message,$button1,$button2,$button3,$title,$footer);
//echo $actionButtons->sendActionButtonsText();

//Detalhe, precisamos ver se compensa fazer algo para incluir o código do pais, ou não.. pois se a pessoa for mandar mensagem para códigos de outros paises.. acho que podemos obrigar isso na hora de cadastrar o telefone, selecionar a bandeira do país igual na suri.

//Detalhe 2, ver se dá pra mandar a MESSAGEID em qualquer tipo de envio de mensagem para marcar a mensagem que estamos respondendo, ou isso é só na simplyTEXT


// Definir a classe para pegar todas os chats em aberto. Nesse caso eu acredito que não vamos usar, pois como tem os setores, não sei como fazer esse filtro.
//https://developer.z-api.io/chats/get-chats
class GetLastChats { 
    private $instancia; 
    private $token;
    private $pagination; 
    private $count; 

    public function __construct($instancia,$token,$pagination = 1, $count = 50){
        $this->instancia = $instancia;
        $this->token = $token; 
        $this->pagination = $pagination;
        $this->count = $count;
    }

    public function getLastChats(){
        $client = curl_init("https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/chats?page=".$this->pagination."&pageSize=".$this->count);

	    curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'GET');
	    curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($client, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($client, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($client, CURLOPT_TIMEOUT, 3); 
    
	    $response = curl_exec($client);
        
        $info = curl_getinfo($client);
    
        if ($info["http_code"] != 200) {
            $response = json_encode(array("status" => $info["http_code"], "error" => curl_error($client)));
        }   
    
        curl_close($client); 
    
        return $response;
  }
}

// Definir a classe para pegar todas as mensagens de um chat. Isso pode ser últil para trazer mensagens que foram escritas direto pelo celular, ai comparar se a messageId consta no banco, se não incluir.
//https://developer.z-api.io/chats/get-message-chats
class GetLastMessage { 
    private $instancia; 
    private $token; 
    private $phone;
    private $aumont;
    
    public function __construct($instancia,$token,$phone,$aumont = 25){
        $this->instancia = $instancia;
        $this->token = $token; 
        $this->phone = $phone;
        $this->aumont = $aumont;
    }
    
    public function getLastMessages(){
        //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}

        $client = curl_init("https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/chat-messages/".$this->phone."?aumont=".$this->aumont."");

	    curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'GET');
	    curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($client, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($client, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($client, CURLOPT_TIMEOUT, 3);  

	    $response = curl_exec($client);
        
        $info = curl_getinfo($client);

        if ($info["http_code"] != 200) {
            $response = json_encode(array("status" => $info["http_code"], "error" => curl_error($client)));
        }   

        curl_close($client); 

        print_r($response);

        return $response;    
      }
}


// Definir a classe de envio de texto apenas, mas posso enviar um MESSAGEID para marcar como respondendo ela.
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

    public function sendSimpleText(){
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
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $data,
          CURLOPT_HTTPHEADER => array(
            "content-type: application/json"
          ),
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_CONNECTTIMEOUT => 5
        ));
    
        $response = curl_exec($client);
        $err = curl_error($client);
        $MessageID = json_decode($response);
        $MessageID = $MessageID->messageId;
    
        curl_close($client);
    
        if (strpos($response, 'erro') !== false) {return "erro";} else { return $MessageID; }
    }
}


// Definir a classe de botões de texto apenas (bom para departamentos, podemos usar @Financeiro para diferenciar da palavra Financeiro)
class ActionButtons { 
    private $instancia; 
    private $token; 
    private $phone; 
    private $message; 
    private $button1; 
    private $button2; 
    private $button3; 
    private $title; 
    private $footer; 

    public function __construct($instancia,$token,$phone,$message,$button1,$button2,$button3,$title,$footer){
        $this->instancia = $instancia;
        $this->token = $token; 
        $this->phone = $phone;
        $this->message = $message;
        $this->button1 = $button1;
        $this->button2 = $button2;
        $this->button3 = $button3;
        $this->title = $title;
        $this->footer = $footer;
    }

    public function sendActionButtonsText(){
        //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}
        $client = curl_init();
    
        if ($this->title == ""){$this->title = "Por favor escolha uma opção";}
        if ($this->footer == ""){$this->footer = "Selecione o botão abaixo:";}
    
        $data = json_encode(array(
        "phone"  => $this->phone,
        "message" => $this->message,
        "title" => $this->title,
        "footer" => $this->footer,
        "buttonActions" =>  array(
            !empty($this->button1) ? array('id'=> '1','type'=> 'REPLY','label' => $this->button1) : null,
            !empty($this->button2) ? array('id'=> '2','type'=> 'REPLY','label' => $this->button2) : null,
            !empty($this->button3) ? array('id'=> '3','type'=> 'REPLY','label' => $this->button3) : null
        )  
        ));
    
        curl_setopt_array($client, array(
          CURLOPT_URL => "https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/send-button-actions",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $data,
          CURLOPT_HTTPHEADER => array(
            "content-type: application/json"
          ),
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_CONNECTTIMEOUT => 5
        ));
    
        $response = curl_exec($client);
        $err = curl_error($client);
        $MessageID = json_decode($response);
        $MessageID = $MessageID->messageId;
    
        curl_close($client);
    
        if (strpos($response, 'erro') !== false) {return "erro";} else { return $MessageID; }
        //if ($err) {return "Erro: " . $err;} else { return $response; }
    }
}

// Definir a classe de envio de botões de texto apenas, mas com uma imagem anexada no começo.
class SendImageButtonsText {
    private $instancia; 
    private $token; 
    private $phone; 
    private $message; 
    private $image; 
    private $button1;
    private $button2;
    private $button3;

    public function __construct($instancia,$token,$phone,$message,$image,$button1,$button2,$button3){
        $this->instancia = $instancia;
        $this->token = $token; 
        $this->phone = $phone;
        $this->message = $message;
        $this->image = $image;
        $this->button1 = $button1;
        $this->button2 = $button2;
        $this->button3 = $button3;
    }

    public function sendImageButtonsText(){
        //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}

        $client = curl_init();
    
        $data = json_encode(array(
            "phone"  => $this->phone,
            "message" => $this->message,
            "buttonList" =>  array(
              "image" => $this->image,
              "buttonActions" =>  array(
            !empty($this->button1) ? array('id'=> '1','label' => $this->button1) : null,
            !empty($this->button2) ? array('id'=> '2','label' => $this->button2) : null,
            !empty($this->button3) ? array('id'=> '3','label' => $this->button3) : null
                  ))
            ));
    
        curl_setopt_array($client, array(
          CURLOPT_URL => "https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/send-button-list",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $data,
          CURLOPT_HTTPHEADER => array(
            "content-type: application/json"
          ),
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_CONNECTTIMEOUT => 5
        ));
    
        $response = curl_exec($client);
        $err = curl_error($client);
        $MessageID = json_decode($response);
        $MessageID = $MessageID->messageId;
    
        curl_close($client);
    
        if (strpos($response, 'erro') !== false) {return "erro";} else { return $MessageID; }
    }
}

// Definir a classe de envio de botões de texto apenas, mas abrindo uma janela explicativa.
class SendMenuButtonsList { 
    private $instancia; 
    private $token; 
    private $phone; 
    private $message; 
    private $listName;
    private $title;
    private $button1Description;
    private $button2Description;
    private $button3Description;
    private $button1Title;
    private $button2Title;
    private $button3Title;

    public function __construct($instancia,$token,$phone,$message,$listName,$listTitle,$button1Description,$button2Description,$button3Description,$button1Title,$button2Title,$button3Title){
        $this->instancia = $instancia;
        $this->token = $token; 
        $this->phone = $phone;
        $this->message = $message;
        $this->listName = $listName;
        $this->listTitle = $listTitle;
        $this->button1Description = $button1Description;
        $this->button2Description = $button2Description;
        $this->button3Description = $button3Description;
        $this->button1Title = $button1Title;
        $this->button2Title = $button2Title;
        $this->button3Title = $button3Title;
    }

    public function sendMenuButtonsList(){
        //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}

        if($this->listTitle == "" || $this->listTitle == null || strlen($this->listTitle) <= 1){$this->listTitle = "Selecione a melhor opção";}

        $client = curl_init();

        $buttons = array();
        if ($this->button1Description != "" && $this->button1Description != null) {
            $buttons[] = array('id'=> '1','description' => $this->button1Description,'title' => $this->button1Title);
        }
        if ($this->button2Description != "" && $this->button2Description != null) {
            $buttons[] = array('id'=> '2','description' => $this->button2Description,'title' => $this->button2Title);
        }
        if ($this->button3Description != "" && $this->button3Description != null) {
            $buttons[] = array('id'=> '3','description' => $this->button3Description,'title' => $this->button3Title);
        }

        $data = json_encode(array(
        "phone"  => $this->phone,
        "message" => $this->message,
        "optionList" =>  array(
            "title" =>  $this->listTitle,
            "buttonLabel" => $this->listName,
            "options" =>  $buttons) 
        ));

        curl_setopt_array($client, array(
        CURLOPT_URL => "https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/send-option-list",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            "content-type: application/json"
        ),
        ));
        $response = curl_exec($client);
        $err = curl_error($client);
        $MessageID = json_decode($response);
        $MessageID = $MessageID->messageId;
        
        curl_close($client);
        
        if (strpos($response, 'erro') !== false) {return "erro";} else { return $MessageID; }
    }
}

// Definir a classe de envio de arquivo de mídia (qualquer tipo dos permitidos)
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

    public function sendMedia(){
        //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}
        $extension = strtolower(pathinfo($this->LinkMedia, PATHINFO_EXTENSION));

        if (strpos($extension, 'acc') !== false) {$MediaType = "audio";}
        elseif (strpos($extension, 'amr') !== false) {$MediaType = "audio";}
        elseif (strpos($extension, 'mpeg') !== false) {$MediaType = "audio";}
        elseif (strpos($extension, 'ogg') !== false) {$MediaType = "audio";}
        elseif (strpos($extension, 'jpg') !== false) {$MediaType = "image";}
        elseif (strpos($extension, 'png') !== false) {$MediaType = "image";}
        elseif (strpos($extension, 'mp4') !== false) {$MediaType = "video";}
        elseif (strpos($extension, '3gpp') !== false) {$MediaType = "video";}
        elseif (strpos($extension, 'webp') !== false) {$MediaType = "sticker";}
        elseif (strpos($extension, 'pdf') !== false) {$MediaType = "document";}
        elseif (strpos($extension, 'doc') !== false) {$MediaType = "document";}
        elseif (strpos($extension, 'dot') !== false) {$MediaType = "document";}
        else{ $MediaType = "document"; }

        $client = curl_init();
    
        $data = json_encode(array(
            "phone"  => $this->phone,
            $MediaType => $this->LinkMedia,
            "caption" =>  "Anexo",
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
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $data,
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json"
          ),
        ));
    
        $response = curl_exec($client);
        $err = curl_error($client);
        $MessageID = json_decode($response);
        $MessageID = $MessageID->messageId;
        curl_close($client);
    
        if (strpos($response, 'error') !== false) {return "erro";} else { return $MessageID; }
    }
}

// Definir a classe para envio de localização
class SendLocation {
    private $instancia; 
    private $token; 
    private $phone; 
    private $title;
    private $address;
    private $latitude;
    private $longitude;

    public function __construct($instancia,$token,$phone,$title,$address,$latitude,$longitude){
        $this->instancia = $instancia;
        $this->token = $token; 
        $this->phone = $phone;
        $this->title = $title;
        $this->address = $address;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function sendLocation(){
        //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}

        $client = curl_init();
    
        $data = json_encode(array(
            "phone"  => $this->phone,
            "title"  => $this->title,
            "address"  => $this->address,
            "latitude"  => $this->latitude,
            "longitude"  => $this->longitude
        ));
    
        curl_setopt_array($client, array(
          CURLOPT_URL => "https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/send-location",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $data,
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json"
          ),
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_CONNECTTIMEOUT => 5
        ));
    
        $response = curl_exec($client);
        $err = curl_error($client);
        $MessageID = json_decode($response);
        $MessageID = $MessageID->messageId;
    
        curl_close($client);
    
        if (strpos($response, 'error') !== false) {return "erro";} else { return $MessageID; }
    }
}

// Definir a classe para deletar uma mensagem enviada. Isso vai apagar para todos, se ainda for possível.
class DeleteMessage { 
    private $instancia; 
    private $token; 
    private $phone; 
    private $messageId; 

    public function __construct($instancia,$token,$phone,$messageId){
        $this->instancia = $instancia;
        $this->token = $token; 
        $this->phone = $phone;
        $this->messageId = $messageId;
    }

    public function deleteMessage(){
        //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}

        $client = curl_init();
    
        curl_setopt_array($client, array(
          CURLOPT_URL => "https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/messages?messageId=".$this->messageId."&phone=".$this->phone."&owner=true",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "DELETE",
        ));
    
        $response = curl_exec($client);
        $Result = json_decode($response);
        $Result = $Result->value;
        $err = curl_error($client);
    
        curl_close($client);
    
        if (strpos($response, 'erro') !== false) {return "erro";}
        elseif (strpos($response, 'true') !== false) {return "deleted";}else{return "invalid";}
    }
}

// Definir a classe para marcar uma mensagem recebida como lida.
class ReadSingleMessage { 
    private $instancia; 
    private $token; 
    private $phone; 
    private $messageId; 

    public function __construct($instancia,$token,$phone,$messageId){
        $this->instancia = $instancia;
        $this->token = $token; 
        $this->phone = $phone;
        $this->messageId = $messageId;
    }

    public function readSingleMessage(){
        //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}

        $client = curl_init();
    
        $data = json_encode(array(
            "phone"  => $this->phone,
            "messageId" => $this->messageId
        ));
    
        curl_setopt_array($client, array(
          CURLOPT_URL => "https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/read-message",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $data,
          CURLOPT_HTTPHEADER => array(
            "content-type: application/json"
          ),
        ));
    
        $response = curl_exec($client);
        $err = curl_error($client);
    
        curl_close($client);
    
        if (strpos($response, 'erro') !== false) {return "erro";}
        elseif (strpos($response, 'true') !== false) {return "read";}else{return "invalid";}
    }
}


// Definir a classe para marcar um chat inteiro como lido.
class ReadChatMessages { 
    private $instancia; 
    private $token; 
    private $phone; 
    private $action; 

    public function __construct($instancia,$token,$phone,$action){
        $this->instancia = $instancia;
        $this->token = $token; 
        $this->phone = $phone;
        $this->action = $action;
    }

    public function readChatMessages(){
        //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}

        $client = curl_init();
    
        //$action pode ser read ou unread
        if($this->action == "" || $this->action == null || strlen($this->action) <= 1){$this->action = "read";}
    
        $data = json_encode(array(
            "phone"  => $this->phone,
            "action" => $this->action
        ));
    
        curl_setopt_array($client, array(
          CURLOPT_URL => "https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/modify-chat",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $data,
          CURLOPT_HTTPHEADER => array(
            "content-type: application/json"
          ),
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_CONNECTTIMEOUT => 5
        ));
    
        $response = curl_exec($client);
        $err = curl_error($client);
    
        curl_close($client);
    
        if (strpos($response, 'erro') !== false) {return "erro";}
        elseif (strpos($response, 'true') !== false) {return $this->action;}else{return "invalid";}
    }
}

// Definir a classe que vai verificar se o Whats já tá conectado ou vai pegar o QRCode para conectar. 
class GetQRCode { 
    private $instancia; 
    private $token; 

    public function __construct($instancia,$token){
        $this->instancia = $instancia;
        $this->token = $token; 
    }

    public function getQRCode(){
        $client = curl_init("https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/qr-code");

        curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($client, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($client, CURLOPT_TIMEOUT, 3);

        $response = curl_exec($client);
        $QR = json_decode($response);
        $QR = $QR->value;
        $err = curl_error($client);
        curl_close($client);

        //Se já estiver online, apenas vou enviar o link da imagem ONLINE.
        if (strpos($response, 'connected":true') !== false) {$image = "includes/QR_images/online.png";} 
          
        else {
        //LIB que gera a imagem do QR code.
        include($_SERVER['DOCUMENT_ROOT'] . '/webhooks/zapi/api/includes/phpqrcode/qrlib.php');
        //QRcode::png($QR, 'includes/QR_images/'.$this->instancia.'.png', 'L', 4, 4);

        //Pegando link da nova imagem.
        $image = "includes/QR_images/".$this->instancia.".png";
        }

        return $response;
    }
}

// Definir a classe que vai desconectar o telefone da instancia, ou seja, precisar ler o QR code depois novamente.
class Disconnect { 
    private $instancia; 
    private $token;

    public function __construct($instancia,$token){
        $this->instancia = $instancia;
        $this->token = $token; 
    }

    public function disconnect(){
        $client = curl_init("https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/disconnect");

        curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($client, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($client, CURLOPT_TIMEOUT, 3);

        $response = curl_exec($client);
        $err = curl_error($client);
        curl_close($client);

        //Se já estiver online, apenas vou enviar o link da imagem ONLINE.
        if (strpos($response, 'erro') !== false) {return "erro";}else{return "executado";}
        //if ($err) {return "erro";} else { return "executado"; }
    }
}

// Definir a classe para checagem se o telefone para o qual vamos enviar mensagem possui whatsapp. (ESSA É BEM IMPORTANTE)
class CheckClientPhone {
  private $instancia; 
  private $token; 
  private $phone;

  public function __construct($instancia,$token,$phone){
      $this->instancia = $instancia;
      $this->token = $token; 
      $this->phone = $phone;
  }

  public function checkClientPhone(){
    //if (strlen($this->phone) <= 11){$this->phone = "55".$this->phone;}

    $client = curl_init("https://api.z-api.io/instances/".$this->instancia."/token/".$this->token."/phone-exists/".$this->phone."");

    curl_setopt($client, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($client, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($client, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($client, CURLOPT_TIMEOUT, 3);

    $response = curl_exec($client);
    $err = curl_error($client);
    curl_close($client);

    if (strpos($response, 'erro') !== false) {return "erro";}
    else{
      if (strpos($response, 'false') !== false) {return "invalido";}else{return "ok";}
      }
    }
  }


//$actionButtons = new SendSimpleText($instancia,$token,"5551998957704","teste",$ReplyMessageId);
//echo $actionButtons->SendSimpleText();


echo time();
?>