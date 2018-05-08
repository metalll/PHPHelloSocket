<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\MyChatWrapper\MyChatApplication;
use Ratchet\MyChatWrapper\MyChatWsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\ComponentInterface; 
use React\EventLoop\Factory as LoopFactory;

    // Make sure composer dependencies have been installed
    require dirname(__DIR__) . '/vendor/autoload.php';
    

class MyChat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        echo "On open \n";

        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
      echo "Message : $msg \n";  
      foreach ($this->clients as $client) {
            if ($from != $client) {
                $client->send($msg);          
            }
            if ($from == $client) {
                if ($msg == "closeCorrect") {
                   $this->closeConnectionCorrect($from);
                } elseif ($msg == "closeIncorrect") {
                    $this->closeConnectionInCorrect($from);
                }    
            }   
        }
    }


    public function closeConnectionInCorrect(ConnectionInterface $conn) {
        if (!$conn->__isset("isCorrectClose")) {
            $conn->__unset("isCorrectClose");
        }
        $conn->__set("isCorrectClose", false);
        $conn->close();
    }


    public function closeConnectionCorrect(ConnectionInterface $conn) {
        if (!$conn->__isset("isCorrectClose")) {
            $conn->__unset("isCorrectClose");
        }
        $conn->__set("isCorrectClose", true);
        $conn->close();
    }

    public function onClose(ConnectionInterface $conn) {
    
      if ($conn->__get("isCorrectClose") === true) {    
          echo  "Correct Close \n";
      } else {
         echo  "Incorrect Close \n";
      }
      
      $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}   

    $wsServer = new MyChatWsServer (new MyChat);

    $app = IoServer::factory(new HttpServer($wsServer));
    $wsServer -> enableKeepAlive(LoopFactory::create(),10);
  
    $app->run();
