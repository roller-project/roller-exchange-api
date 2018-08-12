<?php
namespace PHPSocketIO;
use Symfony\Component\HttpFoundation\Session;

class SocketIO
{
    protected $baseEvent;
    protected $eventHttp;
    protected $listenHost;
    protected $listenPort;

    protected $eventBufferEvents=array();
    protected $requests=array();

    protected $onConnectCallback;

    protected $namespace = 'socket.io';

    protected $httpRequests = [];

    protected $sessionArray;

    protected $sockets = null;

    public function __construct(\EventBase $baseEvent = null)
    {
        if ($baseEvent === null) {
            $baseEvent = new \EventBase();
        }
        $this->baseEvent = $baseEvent;
        $this->onConnectCallback = function(){};
    }

    public function listen($host, $port = null)
    {
        if ($port === null) {
            $port = $host;
            $host = '0.0.0.0';
        }
        $this->listenHost = $host;
        $this->listenPort = $port;

        return $this;
    }

    protected function eventBufferEventGc()
    {
        foreach ($this->eventBufferEvents as $eventBufferEvent) {
            $eventBufferEvent->setCallbacks(null, null, null);
            $eventBufferEvent->free();
        }
        foreach ($this->requests as $request) {
            $request->closeConnection();
        }
        $this->requests=array();
        $this->eventBufferEvents=array();
    }

    public function dispatch()
    {
        $this->createEventListener();
        while (true) {
            if ($this->baseEvent->gotExit()) {
                break;
            }
            $this->baseEvent->dispatch();
            $this->eventBufferEventGc();
        }
    }

    public function onConnect($callback)
    {
        $this->onConnectCallback = $callback;

        return $this;
    }

    public function onRequest($query, $callback)
    {
        $this->httpRequests[] = [$query, $callback];

        return $this;
    }

    protected function initSession()
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener('request.init.session', function(Event\RequestEvent $requestEvent){
            $request = $requestEvent->getRequest();
            $request->setSession(
                    new Session\Session(
                            new SessionStorage\MemoryStorage()
                    )
            );
        });
    }

    protected function createEventListener()
    {
        $this->eventHttp = new \EventHttp($this->baseEvent);
        $this->eventHttp->bind($this->listenHost, $this->listenPort);
        $this->initSession();
        $this->eventHttp->setDefaultCallback(function($request){
            $connection = new Connection($this->baseEvent, $this->namespace, function(\EventBufferEvent $event) use ($request) {
                $this->eventBufferEvents[]=$event;
                $this->requests[]=$request;
                $this->baseEvent->stop();
            });
            $request->getConnection()->getPeer($address, $port);
            $connection->parseHTTP($request);
            call_user_func($this->onConnectCallback, $connection);
        });
        foreach ($this->httpRequests as $httpRequest) {
            list($query, $callback) = $httpRequest;
            $this->eventHttp->setCallback($query, function($request) use ($callback) {
                $connection = new Connection($this->baseEvent, $this->namespace, function(\EventBufferEvent $event) use ($request) {
                    $this->eventBufferEvents[]=$event;
                    $this->requests[]=$request;
                    $this->baseEvent->stop();
                });
                $connection->setEventHTTPRequest($request);
                $callback($connection, $request);
            });
        }
    }

    public function __destruct()
    {
        $this->baseEvent = null;
        $this->eventListener = null;
        $this->onConnectCallback = null;
    }

    /**
     *
     * @param  string         $endpoint
     * @return SocketIOSocket
     */
    public function of($endpoint = null)
    {
        return new SocketIOSocket($endpoint);
    }

    /**
     *
     * @return SocketIOSocket
     */
    public function getSockets()
    {
        if (!$this->sockets) {
            $this->sockets = $this->of();
        }

        return $this->sockets;
    }

    public function on($eventName, $callback)
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener("client.$eventName", $callback);

        return $this;
    }

    public function emit($eventName, $message)
    {
        $messageEvent = new Event\MessageEvent();
        $messageEvent->setMessage(array(
                'event' => $eventName,
                'message' => $message,
            ));
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->dispatch("server.emit", $messageEvent);
    }

}
