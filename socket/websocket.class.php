<?php
class websocket {
    public $event;
    public $master;
    public $Mcache;
    public $sockets = array();
    public $usr = array();
    public $chats=array();

    public function __construct($config) {
        if (substr(php_sapi_name(), 0, 3) !== 'cli') {
            die("请通过命令行模式运行!");
        }
        error_reporting(E_ALL);
        set_time_limit(0);
        ob_implicit_flush();
        $this->event = $config['event'];
        $this->master = $this->WebSocket($config['address'], $config['port']);
        $this->sockets[] = $this->master;
        $this->Mcache = new Memcache;
        $this->Mcache->connect("127.0.0.1", 11211) or die ("Could not connect");
    }
    function WebSocket($address, $port) {
        $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($server, $address, $port);
        socket_listen($server);
        $this->log('开始监听: ' . $address . ' : ' . $port);
        return $server;
    }
    function run() {
       while (true) {
            $changes = $this->sockets;
            @socket_select($changes, $write = NULL, $except = NULL, NULL);//获取发送信息用户进程
            foreach ($changes as $sign) {
                if ($sign == $this->master) {
                    $client = socket_accept($this->master);
                    $this->sockets[] = $client;
                    $k = array_search($sign, $this->sockets);
                    $this->eventoutput('in', array( 'sign' => $sign,"key"=>$k));
                } else {
                    $k = array_search($sign, $this->sockets);
                    $len = socket_recv($sign, $buffer, 2048, 0);
                    if ($len < 7) {
                        socket_close($sign);
                        unset($this->sockets[$k]);
                        unset($this->usr[$k]);
                        $this->eventoutput('out', array( 'sign' => $sign,"key"=>$k));
                        continue;
                    }
                    if (isset($this->usr[$k])) {//没有握手进行握手
                        $buffer = $this->uncode($buffer);
                        $this->eventoutput('msg', array('sign' => $sign, 'msg' => $buffer,"key"=>$k));
                    } else {
                        $this->handshake($sign, $buffer);
                        $this->usr[$k] = 1;
                    }
                }
            }
       }
    }

    function eventoutput($type, $event) { //事件回调
        call_user_func($this->event,$type,$event);
    }

    function handshake($sign, $buffer) {
        $buf = substr($buffer, strpos($buffer, 'Sec-WebSocket-Key:') + 18);
        $key = trim(substr($buf, 0, strpos($buf, "\r\n")));
        $new_key = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
        $new_message = "HTTP/1.1 101 Switching Protocols\r\n";
        $new_message .= "Upgrade: websocket\r\n";
        $new_message .= "Sec-WebSocket-Version: 13\r\n";
        $new_message .= "Connection: Upgrade\r\n";
        $new_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";
        socket_write($sign, $new_message, strlen($new_message));
        return true;
    }
    function uncode($str) {
        $mask = array();
        $data = '';
        $msg = unpack('H*', $str);
        $head = substr($msg[1], 0, 2);
        if (hexdec($head{1}) === 8) {
            $data = false;
        } else if (hexdec($head{1}) === 1) {
            $mask[] = hexdec(substr($msg[1], 4, 2));
            $mask[] = hexdec(substr($msg[1], 6, 2));
            $mask[] = hexdec(substr($msg[1], 8, 2));
            $mask[] = hexdec(substr($msg[1], 10, 2));
            $s = 12;
            $e = strlen($msg[1]) - 2;
            $n = 0;
            for ($i = $s; $i <= $e; $i += 2) {
                $data .= chr($mask[$n % 4] ^ hexdec(substr($msg[1], $i, 2)));
                $n++;
            }
        }
        return $data;
    }
    function code($msg) {
        $msg = preg_replace(array('/\r$/', '/\n$/', '/\r\n$/',), '', $msg);
        $frame = array();
        $frame[0] = '81';
        $len = strlen($msg);
        $frame[1] = $len < 16 ? '0' . dechex($len) : dechex($len);
        $frame[2] = $this->ord_hex($msg);
        $data = implode('', $frame);
        return pack("H*", $data);
    }
    function ord_hex($data) {
        $msg = '';
        $l = strlen($data);
        for ($i = 0; $i < $l; $i++) {
            $msg .= dechex(ord($data{$i}));
        }
        return $msg;
    }
    function log($t) { //控制台输出
        fwrite(STDOUT, iconv('utf-8', 'gbk//IGNORE', "\n".$t . "\n"));
    }
}