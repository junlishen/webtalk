<?php
include 'websocket.class.php';
$config = array(
    //'address' => '172.16.3.9',
    'address' => '192.168.0.25',
    'port' => '12345',
    'event' => 'WSevent', //回调函数的函数名
);
$websocket = new websocket($config);
$websocket->run();
function WSevent($type,$event){
    global $websocket;
    $Mcache = $websocket->Mcache;

    $usrs = $Mcache->get('usrs');
    $usrid = $event['key'];

    $sendOneMsg = array(
        'flag'=> 'all',
        'sign'=>0
    );
    if ($type== 'in') {
        $websocket->log( '客户进入id:' . $usrid);
        return;
    }
    if ($type=='out') {
        $websocket->log('客户退出id:' .$usrid);
        $msgs = array(
            'type'=>"usrout",
            'id'=>$usrid,
            'msg'=>$usrs[$usrid]['name'].' [ID:'.$usrid.']: 已下线!'
        );
        unset($usrs[$usrid]);
        $Mcache->set('usrs',$usrs);
    } elseif ($type=='msg') {
        $recvMsg = json_decode($event['msg'],true);
        $logs = isset($usrs[$usrid]['name'])?$usrs[$usrid]['name'].' [ID:'.$usrid.'] ':$usrid;
        $websocket->log($logs.'消息:' . $event['msg']);

        if($recvMsg['type']=='msg'){
            $usrid = $recvMsg['usrid'];
            $usr = $usrs[$usrid];
            $msgs = array(
                'type'=>"msg",
                'id'=>$usrid,
                'name'=>$usr['name'],
                'pic'=>$usr['pic'],
                'msg'=>$recvMsg['msg'],
                'time'=>date("Y-m-d H:i:s"),
            );
        }else if($recvMsg['type']=='usrinfo'){
            $usrs[$usrid] = $recvMsg['info'];
            unset($usrs[$usrid]['msg']);
            $Mcache->set('usrs',$usrs);
            $websocket->log(json_encode($Mcache->get('usrs')));
            $msgs = array(
                "type"=>"usrin",
                "usrid"=>$event['key'],
                "name"=>$recvMsg['info']['name'],
                "pic"=>$recvMsg['info']['pic'],
                "msg"=>"用户信息"
            );
        }else if($recvMsg['type']=='getallusrinfo'){
            $allInfo = array();
            foreach($usrs as $val){
                $allInfo[] = array(
                    'usrid' => $val['usrid'],
                    'usrnick' => $val['usrnick'],
                    'pic' => $val['pic']
                );
            }
            $msgs = array(
                "type"=>"allusrinfo",
                "msg"=>$allInfo
            );
            $sendOneMsg['flag'] = 'one';
            $sendOneMsg['sign'] = $event['sign'];
        }elseif($recvMsg['type']=="getselfinfo"){

        }

    }
    /*发送信息*/
    $msgs = json_encode($msgs);
    $websocket->log("返回信息：".$msgs);

    $msg = $websocket->code($msgs);
    $msgLen = strlen($msg);

    if($sendOneMsg['flag'] == 'one'){
        socket_write($sendOneMsg['sign'], $msg, $msgLen);//单人发送
    }else{
        $logUsrs = $websocket->sockets;
        unset($logUsrs[0]);
        foreach($logUsrs as $k=>$val){
            socket_write($val, $msg, $msgLen);
        }
    }
}
?>
