<?php
class Client_work
{
    private $client;
    private $port;

    public function __construct($port=false)
    {
        if(!$port)throw new Exception('listen port must be not null.');
        $this->port = $port;
        $this->client = new swoole_client(SWOOLE_SOCK_TCP);
    }

    public function connect()
    {
        if (!$this->client->connect("127.0.0.1", $this->port, 1))throw new Exception(sprintf('Swoole Error: %s', $this->client->errCode));
    }

    public function send($data)
    {
        if ($this->client->isConnected()) {
            if (!is_string($data))$data = json_encode($data);
            return $this->client->send($data);
        } else {
            throw new Exception('Swoole Server does not connected.');
        }
    }

    public function close()
    {
        $this->client->close();
    }
}