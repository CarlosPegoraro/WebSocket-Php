<?php

namespace WebSockets;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\Socket\ConnectorInterface;

date_default_timezone_set('America/Sao_Paulo');

class Socket implements MessageComponentInterface
{
    private $clients;
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->setLog('Servidor Iniciado');
    }

    protected function setLog($log)
    {
        echo date('Y-m-d H:i:s') . ' ' . $log . PHP_EOL;
        fwrite(fopen('logs.log', 'a'), date('Y-m-d H:i:s') . ' ' . $log . PHP_EOL);
    }

    protected function conAtivas()
    {
        return "Conexões ativas: " . count($this->clients);
    }

    protected function getUserOnline($usrID)
    {
        $ret = false;
        foreach ($this->clients as $client) {
            if ($usrID == $client->resourceId) $ret = true;
        }
        return $ret;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $this->setLog("Nova Conexão no Servidor! ({$conn->resourceId})");
        $this->setLog($this->conAtivas());

        $conn->send(
            json_encode(
                array(
                    'from' => 'Servidor',
                    'nick' => "@Usuario{'$conn->resourceId'}",
                    'msg' => "Bem vindo Usuario {$conn->resourceId}!<br> use /help para ajuda"
                )
            )
        );
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        if ($msg == '/help') {
            $from->send(json_encode(array('from' => 'Servidor', 'msg' => 'Comandos disponiveis:<br> /help - Lista de comandos<br> /online - Lista de usuarios online')));
        } elseif ($msg == '/online') {
            $from->send(json_encode(array('from' => 'Servidor', 'msg' => count($this->clients) . ' Usuarios online')));
        } elseif ($msg == '/users') {
            $String = 'Id dos usuarios online:';
            foreach ($this->clients as $client) {
                $String .= ($String!= ""?"<br>":"").$client->resourceId.($from->resourceId == $client->resourceId?" (Voce)":"");
            }
            $from->send(json_encode(array('from' => 'Servidor', 'msg' => $String)));
        } else {
            $privateID = false;
            if (preg_match("/\[0-9]\d*\]/", $msg, $arrResult))$privateID = $arrResult[1];
            $this->setLog("Messagem de {$from->resourceId} Msg: '$msg'" . ($privateID?" para {$privateID}" . ($this->getUserOnline($privateID)?" (Online)":" (Offline)"):""));
            if ($privateID) {
                $UrsIsOnline = $this->getUserOnline($privateID);
                foreach($this->clients as $client) {
                    if($UrsIsOnline) {
                        if($client->resourceId == $from->resourceId) continue;
                        if ($privateID == $client->resourceId) {
                            $this->setLog("A conexão {$client->resourceId} recebeu a mensagem de {$from->resourceId}");
                            $client->send(json_encode(array('from' => 'usuario'.$from->resourceId, 'msg' => "(msg privada)<br>" . trim(str_replace("[$privateID]", "", $msg)))));
                        } else {
                            if($from->resourceId != $client->resourceId) continue;
                            if ($from->resourceId == $client->resourceId) {
                                $client->send(json_encode(array('from' => 'Servidor', 'msg' => "(Que pena, o usuario $privateID não esta conectado")));
                            }
                        }
                    }
                }
            } else {
                foreach ($this->clients as $client) {
                    if($from->resourceId == $client->resourceId) continue;
                    $this->setLog("A conexão {$client->resourceId} recebeu a mensagem de {$from->resourceId}");
                    $client->send(json_encode(array('from' => 'usuario'.$from->resourceId, 'msg' => $msg)));
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        $this->setLog("Conexão encerrada ({$conn->resourceId})");
        $this->setLog($this->conAtivas());
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        $this->setLog("Erro na conexao ID: ({$conn->resourceId})");
    }
}
