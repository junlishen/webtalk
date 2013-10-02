var usrInfo = {usrid:false,name:"君立",mail:"1310009311@qq.com"};
var socketFed = {
    socket:null,
    emptyVal:function(args){
        for(var i in args){
            if((/\s+/gi).test(args[i])||args[i]==""){
                return true;
            }
        }
        return false;
    },
    strJson:function(json){
        return JSON.stringify(json);
    },
    serialzeVal:function(serialzs){
        var val = {}
            ,_nowVal;
        for(var i =0;i<serialzs.length;i++){
            _nowVal = serialzs[i];
            val[_nowVal['name']]=serialzs[i]['value'];
        }
        return val;
    },
    getTime:function(dataParse){
        var time = new Date(dataParse);
        return time.getHours()+":"+time.getMinutes()+":"+time.getSeconds();
    },
    msgLog:function(args){
        var messageTim = socketFed.getTime(args['timeStamp']/1000)
            ,msgData = JSON.parse(args.data)
            ,msgBx = $('#J_msgLogBx')
            ,msgTxtBx = $('#msgBx')
            ,msgInfo;
        switch (msgData['type']){
            case 'usrin':
                usrInfo['usrid'] = msgData['usrid'];
                document.title = JSON.stringify(msgData);
                break;
            case 'usrout':
                document.title = JSON.stringify(msgData);
                break;
            case 'usrinfo':
                this['usrid'] = msgData["usrid"];
                msgTxtBx.text(JSON.stringify(msgData));
                break;
            case 'allusrinfo':
                msgTxtBx.text(JSON.stringify(msgData));
                break;
            case 'getselfinfo':
                msgTxtBx.text(JSON.stringify(msgData));
                break;
            default :
                var temp = '<div class="media"><a class="pull-left" href="#"><img class="media-object" src="'+(msgData['pic']||"/media/null.gif")+'"></a><div class="media-body"><h4 class="media-heading">'+msgData['name']+'<span>'+msgData['time']+' / <a href="#">Reply</a></span></h4><p>'+msgData['msg']+'</p></div></div>';
                msgTxtBx.prepend(temp);
        }
    },
    login:function(){
        var _this = this
            ,loginPop = {};
        loginPop['title'] = '快速登录';
        loginPop['class'] = 'loginPopBox';
        loginPop['ico'] = 'icon-user';/*loginPop['ico'] = 'icon-comments';*/
        loginPop['html'] = '<h4>个人信息</h4><form id="regForm" onsubmit="return false;"><div class="input-icon left"><i class="icon-user-md"></i><input type="text" name="name" placeholder="昵称" class="m-wrap"></div><div class="input-icon left"><i class="icon-envelope"></i><input type="text" name="mail" placeholder="Email 登录使用" class="m-wrap"></div><div class="space10"></div><input type="submit" value="Submit" class="btn blue btn-block"></form>'
        this.windPop(loginPop);
        $(".btn-block","#regForm").click(function(){
            var formVals = _this.serialzeVal($("#regForm").serializeArray());
            if(!_this.emptyVal(formVals)){
                usrInfo = $.extend(usrInfo,formVals);
                _this.windPopClose();
                document.title = _this.strJson(usrInfo);
                return false;
            }else{
                alert("昵称或邮箱未填写");
                return false;
            }
        });
    },
    browser:function(){
        var apps = window.navigator
            ,args = ['appCodeName','appName','appVersion']
            ,msgInfo = []
            ,nowInfo;
        for(var i in args){
            nowInfo = args[i];
            msgInfo.push(nowInfo+": "+apps[nowInfo]);
        }
        return msgInfo.join("\n\n");
    },
    usrFace:function(){
        socketFed.windPop({title:'头像设置','class':'usrFaceBox','ico':'icon-picture','html':$('#shot').val()});
        var usrFacePop = {};
        setTimeout(function(){new camera();},0);
        usrFacePop['title'] = '头像设置';
        usrFacePop['class'] = 'usrFaceBox';
        usrFacePop['ico'] = 'icon-picture';
        usrFacePop['html'] = $('#shot').val();

    },
    windPop:function(args){
        var Temp = '<div id="windPopBox" class="portlet box blue '+(args['class']||'msgPop')+'"><div class="portlet-title line"><div class="caption"><i class="'+(args['ico']||"icon-foursquare")+'"></i>'+(args['title']||"网站提示")+'</div><div class="tools"><a class="remove" href="/"></a></div></div><div class="portlet-body">'+args['html']+'</div></div>';
        $(Temp).appendTo($('body')).css({marginLeft:"-"+$('#windPopBox').width()/2+"px","opacity":0}).animate({opacity:1},240);
        $(".remove",".loginPopBox").click(function(){
            socketFed.windPopClose();
            return false;
        });
    },
    windPopClose:function(){
        $("#windPopBox").animate({opacity:0},240,function(){
            $("#windPopBox").remove();
        });
    },
    loadFun:function(){
        //this.login();
        setTimeout(function(){
            socketFed.usrFace();
        },0);
    },
    socketLoadFun:function(host){
        //var socketHost = host||'172.16.3.9'
        var socketHost = host||'192.168.0.25'
            ,_this = this
            ,textBx = $("textarea:visible",'#sendbox');
            _this['socket'] = new WebSocket("ws://"+socketHost+":12345");
            _this['socket']['onopen'] = function () {
                document.title = '连接成功';
                var sendData = JSON.stringify({'type':'usrinfo','info':{"name":usrInfo['name'],"mail":usrInfo['mail'],'msg':"用户登录","pic":false}});
                _this['socket'].send(sendData);
            }
            _this['socket']['onmessage'] = function (msg) {
                socketFed.msgLog(msg);
            }
            _this['socket']['onclose'] = function () {
                document.title = '断开连接';
            }
        $("#sendboxBt").on({
            click:function(){
                var _sendVal = {"type":"msg","usrid":usrInfo['usrid'],"msg":textBx.val()}
                    ,sendData = JSON.stringify(_sendVal);
                _this['socket'].send(sendData);

                /*if($(this).hasClass('J_connetSocket')){
                    socketLink();
                }else if($(this).hasClass('J_disSocket')){
                    _socket.close();
                    _socket = null;
                }else if($(this).hasClass('J_sendSocket')){
                    var _sendVal = {"type":"msg","usrid":usrInfo['usrid'],"msg":textBx.val()}
                        ,sendData = JSON.stringify(_sendVal);
                    _socket.send(sendData);
                }else if($(this).hasClass('J_getUsrs')){
                    _socket.send('{"type":"getallusrinfo"}');
                }else if($(this).hasClass('J_getSeftInfo')){
                    _socket.send('{"type":"getselfinfo"}');
                }*/
                return false;
            }
        });
    }
};

function camera(boxid,size){
    var _this = this;
    _this['box'] = document.querySelector(boxid||'.cameraBox');
    _this['video'] = _this['box'].querySelector(".video");
    _this['baseCodeText'] = _this['box'].querySelector('.baseCodeText');
    _this['canvas'] = _this['box'].querySelector('.canvas');
    _this['cSize'] = [300,150];
    _this['scamera'] = function (){
        var  context = _this['canvas'].getContext('2d');
        with(context){
            fillRect(0,0,_this['cSize'][0],_this['cSize'][1]);
            setInterval(function(){drawImage(_this['video'],0,0,_this['cSize'][0],_this['cSize'][1]);},180);
            //drawImage(_this['video'],0,0,_this['cSize'][0],_this['cSize'][1]);
        }
        _this['baseCodeText'].innerHTML = _this['canvas'].toDataURL("image/png");
    };
    _this['start'] = function (){
        navigator.getUserMedia =  navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.oGetUserMedia || navigator.msieGetUserMedia || !1;
        window.URL = window.URL || window.webkitURL || window.mozURL || window.msURL || window.oURL;

        if (navigator.getUserMedia) {
            var gumOptions = {video: true, toString: function(){return 'video';}};
            navigator.getUserMedia(gumOptions,function successCallback(stream) {
                _this['video'].setAttribute('src',navigator.getUserMedia==navigator.mozGetUserMedia?stream:(window.URL.createObjectURL(stream) || stream));
            },function errorCallback(error) {
                alert('An error occurred: [CODE ' + error.code + ']');
            });
            _this['video'].play();
        }else {
            navigator.getUserMedia("video", function (stream) {
                _this['video'].setAttribute('src',window.webkitURL.createObjectURL(stream));
                _this['video'].play();
            }, function (error) {
                alert(error);
            });
        }
    };
    _this['stop'] = function (){
        _this['video'].stop();
        //_this['video'].setAttribute('src','');
    };
    _this['loadFun'] = function (){
        _this['start']();
        _this['scamera']();
        _this['box'].querySelector('.cameraStart').onclick = function(){
            _this['start']();
        };
        _this['box'].querySelector('.cameraPic').onclick = function(){
            _this['scamera']();
        };
        _this['box'].querySelector('.cameraStop').onclick = function(){
            _this['stop']();
        };
    }();
};

