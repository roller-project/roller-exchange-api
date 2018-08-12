<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PHPSocketIO\SocketIO;
use PHPSocketIO\Connection;
use PHPSocketIO\Response\Response;
use PHPSocketIO\Event;

class Serverapi extends ServerController {
	public function run(){
		$api = new \Binance\API('','');
		$api->ticker("BTCUSDT", function($api, $symbol, $ticker) {
			print_r($ticker);
		});
	}

    public function svg(){
        $socketio = new SocketIO();
        $socketio
        ->listen(8080)
        ->onConnect(function(Connection $connection) use ($socketio) {
            list($host, $port) = $connection->getRemote();
            echo "connected $host:$port\n";
        })
        ->dispatch();
    }
	public function socketio(){
		$server = IoServer::factory(new HttpServer(new WsServer(new Server)), 8888);
		$server->run();
		
	}
}
/**
 * 
 */
class Server implements MessageComponentInterface
{
	
	protected $clients;

    public function __construct() {
        $this->clients = array();
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients[$conn->resourceId] = $conn;
        echo "New connection! ({$conn->resourceId})\n";
        $arv = ["700","800","900"];
        $conn->send(array_rand($arv,1));
        
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }

        $client = $this->clients[$from->resourceId];
        $arv = ["700","800","900"];
        while(true){
        	$client->send(array_rand($arv,1));
        	sleep(3);
    	}
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}