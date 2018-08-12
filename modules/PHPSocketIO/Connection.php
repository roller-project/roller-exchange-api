<?php
namespace PHPSocketIO;

class Connection implements ConnectionInterface
{
    const MAX_INPUT = 4000000;   // 4MB

    protected $baseEvent;
    protected $eventBufferEvent = null;

    protected $request;
    protected $eventHTTPRequest;
    protected $namespace;

    protected $timeoutEvent;

    protected $shutdownAfterSend = false;

    protected $onReceiveCallbacks=[];
    protected $onWriteBufferEmptyCallbacks=[];

    protected $eventBufferEventGCCallback;

    protected $remote;

    protected $registeredEventGroups = [];

    /**
     *
     * @var Adapter\ProtocolProcessorInterface
     */
    protected $protocolProcessor;

    public function __construct(\EventBase $baseEvent, $namespace, $eventBufferEventGCCallback)
    {
        $this->baseEvent = $baseEvent;
        $this->namespace = $namespace;
        $this->eventBufferEventGCCallback = $eventBufferEventGCCallback;
    }

    public function parseHTTP(\EventHttpRequest $eventHTTPRequest)
    {
        $this->setEventHTTPRequest($eventHTTPRequest);
        $headers = $eventHTTPRequest->getInputHeaders();
        $server = array(
            'REQUEST_URI' => $eventHTTPRequest->getUri(),
        );
        list($server['REMOTE_ADDR'], $server['REMOTE_PORT']) = $this->getRemote();
        $server['REQUEST_METHOD'] = array_search($eventHTTPRequest->getCommand(), array(
            'GET' => \EventHttpRequest::CMD_GET ,
            'POST' => \EventHttpRequest::CMD_POST ,
            'HEAD' => \EventHttpRequest::CMD_HEAD ,
            'PUT' => \EventHttpRequest::CMD_PUT ,
            'DELETE' => \EventHttpRequest::CMD_DELETE ,
            'OPTIONS' => \EventHttpRequest::CMD_OPTIONS ,
            'TRACE ' => \EventHttpRequest::CMD_TRACE ,
            'CONNECT ' => \EventHttpRequest::CMD_CONNECT ,
            'PATCH ' => \EventHttpRequest::CMD_PATCH ,
        ));
        $request = Http\Http::parseRequest(
                $server,
                $headers,
                $eventHTTPRequest->getInputBuffer()->read(static::MAX_INPUT));
        $request->setConnection($this);
        $this->setRequest($request);
        Http\Http::handleRequest($request);
        $this->prepareEventGroup();
        $this->emitPendingEvent();
        $this->dispatchQueueEventEvent();
    }

    protected function dispatchQueueEventEvent()
    {
        if(!$this->getRequest()->getSession()){
            return;
        }
        $queueEvent = $this->getRequest()->getSession()->get('queueEvent', []);
        $dispatcher = Event\EventDispatcher::getDispatcher();
        foreach ($queueEvent as $eventName => &$events) {
            foreach ($events as $index => $event) {
                if (time() - $event['timestamp'] < 5) {
                    $receive = $dispatcher->dispatch($eventName, $event['event'], $event['group']);
                    if ($receive) {
                        unset($events[$index]);
                    }
                } else {
                    unset($events[$index]);
                }
            }
            if (count($queueEvent[$eventName]) == 0) {
                unset($queueEvent[$eventName]);
            }
        }
        $this->getRequest()->getSession()->set('queueEvent', $queueEvent);
    }

    protected function emitPendingEvent()
    {
        if(!$this->getRequest()->getSession()){
            return;
        }
        $pendingEmitEvent = $this->getRequest()->getSession()->get('pendingEmitEvent', []);
        foreach ($pendingEmitEvent as $eventName => &$events) {
            foreach ($events as $index => $event) {
                if (time() - $event['timestamp'] < 5) {
                    $receive = $this->dispatchEmitEvent($eventName, $event['message']);
                    if ($receive) {
                        unset($events[$index]);
                    }
                } else {
                    unset($events[$index]);
                }
            }
            if (count($pendingEmitEvent[$eventName]) == 0) {
                unset($pendingEmitEvent[$eventName]);
            }
        }
        $this->getRequest()->getSession()->set('pendingEmitEvent', $pendingEmitEvent);
    }

    protected function prepareEventGroup()
    {
        $this->registeredEventGroups[$this->getSessionId()] = true;
    }

    public function setRequest(Request\Request $request)
    {
        $this->request = $request;
    }

    public function getSessionId()
    {
        if ($this->getRequest() && $this->getRequest()->getSession()) {
            return $this->getRequest()->getSession()->getId();
        }

        return null;
    }

    public function setEventHTTPRequest(\eventHTTPRequest $eventHTTPRequest)
    {
        $this->eventHTTPRequest = $eventHTTPRequest;
    }

    public function sendResponse(Response\Response $response)
    {
        $buffer = $this->eventHTTPRequest->getOutputBuffer();
        $buffer->add($response->getContent());
        if ($contentType = $response->headers->get('Content-Type')) {
            $this->eventHTTPRequest->addHeader('Content-Type', $contentType, \EventHttpRequest::OUTPUT_HEADER);
        }
        $this->eventHTTPRequest->sendReply($response->getStatusCode(), $response->getStatusCode());
        $this->eventHTTPRequest->free();
    }

    protected function getEventBufferEvent()
    {
        if (!$this->eventBufferEvent) {
            $this->eventBufferEvent = $this->eventHTTPRequest->getBufferEvent();
            $this->eventBufferEvent->setCallbacks(function(){
                $data  = $this->eventBufferEvent->read(4096);
                $dispatcher = Event\EventDispatcher::getDispatcher();
                $messageEvent = new Event\MessageEvent();
                $messageEvent->setMessage($data);
                $messageEvent->setConnection($this);
                $dispatcher->dispatch("socket.receive", $messageEvent, $this->getSessionId());
            }, function(){
                if ($this->shutdownAfterSend) {
                    $this->free();
                }
            }, function(){
            });
            $this->eventBufferEvent->enable(\Event::READ | \Event::WRITE);
        }

        return $this->eventBufferEvent;
    }

    public function write(Response\ResponseInterface $response, $shutdownAfterSend = false)
    {
        $this->shutdownAfterSend = $shutdownAfterSend;
        $this->getEventBufferEvent()->write($response);
    }

    public function isConnectionClose()
    {
        return $this->shutdownAfterSend;
    }

    /**
     *
     * @return Request\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function getRemote()
    {
        if (!$this->remote && $this->eventHTTPRequest) {
            $this->eventHTTPRequest->getConnection()->getPeer($address, $port);
            $this->remote = array($address, $port);
        }

        return $this->remote;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function free()
    {
        if (!$this->baseEvent) {
            return;
        }
        $this->clearTimeout();
        $this->unregisterEvent();
        if ($this->request && $this->request->getSession()) {
           $this->request->getSession()->save();
        }
        if ($this->eventBufferEvent) {
            call_user_func($this->eventBufferEventGCCallback, $this->eventBufferEvent, $this->eventHTTPRequest);
        }
        $this->baseEvent = null;
        $this->eventBufferEvent = null;
        $this->request = null;
        $this->eventHTTPRequest = null;
        $this->onReceiveCallbacks = null;
        $this->onWriteBufferEmptyCallbacks = null;
        $this->eventBufferEventGCCallback = null;
        $this->onReceiveCallbacks = null;
    }

    public function setTimeout($timer, $callback)
    {
         $this->timeoutEvent = new \Event($this->baseEvent, -1, \Event::TIMEOUT, function($fd, $what, $event) use ($callback) {
             $callback();
         });
         $this->timeoutEvent->data = $this->timeoutEvent;
         $this->timeoutEvent->addTimer($timer);
    }

    public function clearTimeout()
    {
        if ($this->timeoutEvent === null) {
            return;
        }
        $this->timeoutEvent->data = null;
        $this->timeoutEvent->free();
        $this->timeoutEvent = null;
    }

    public function __destruct()
    {
        $this->free();
    }

    public function on($eventName, $callback)
    {
        $group[] = $this->getSessionId();
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener("client.$eventName", $callback, $group);

        return $this;
    }

    public function queueEvent($eventName, $event = null, $group = null)
    {
        $queueEvent = $this->getRequest()->getSession()->get('queueEvent', []);
        $queueEvent[$eventName][] = array(
            'timestamp' => time(),
            'event' => $event,
            'group' => $group,
        );
        $this->getRequest()->getSession()->set('queueEvent', $queueEvent);
    }

    public function emit($eventName, $message)
    {
        if ($this->isConnectionClose() || !$this->dispatchEmitEvent($eventName, $message)) {
            $this->queuePendingEmitEvent($eventName, $message);
        }

        return $this;
    }

    public function queuePendingEmitEvent($eventName, $message)
    {
        $pendingEmitEvent = $this->getRequest()->getSession()->get('pendingEmitEvent', []);
        $pendingEmitEvent[$eventName][] = array(
            'timestamp' => time(),
            'message' => $message,
        );
        $this->getRequest()->getSession()->set('pendingEmitEvent', $pendingEmitEvent);
        return $this;
    }

    protected function dispatchEmitEvent($eventName, $message)
    {
        $messageEvent = new Event\MessageEvent();
        $messageEvent->setMessage(array(
                'event' => $eventName,
                'message' => $message
                ));
        $messageEvent->setConnection($this);
        $dispatcher = Event\EventDispatcher::getDispatcher();

        return $dispatcher->dispatch(
            "server.emit",
            $messageEvent,
            $this->getSessionId()
        );
    }

    public function onRecieve($callback)
    {
        $this->onReceiveCallbacks[]=$callback;

        return $this;
    }

    public function onWriteBufferEmpty($callback)
    {
        $this->onWriteBufferEmptyCallbacks[]=$callback;

        return $this;
    }

    protected function unregisterEvent()
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $groups = array_keys($this->registeredEventGroups);
        foreach ($groups as $group) {
            $dispatcher->removeGroupListener($group);
        }
        $this->registeredEventGroups = [];
    }

}
