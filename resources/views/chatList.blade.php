<html>
<head>
  <meta charset="UTF-8">
  <title>聊天室</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
    <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css'>
    <link rel="stylesheet" href="{{ asset('css/styleChat.css') }}">
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/jquery-sinaEmotion-2.1.0.min.css') }}" rel="stylesheet">

    <script type="text/javascript" src="{{ asset('js/swfobject.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/web_socket.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/jquery-sinaEmotion-2.1.0.min.js') }}"></script>

    <script type="text/javascript">
        if (typeof console == "undefined") {    this.console = { log: function (msg) {  } };}
        // 如果浏览器不支持websocket，会使用这个flash自动模拟websocket协议，此过程对开发者透明
        WEB_SOCKET_SWF_LOCATION = "{{ asset('swf/WebSocketMain.swf') }}";
        // 开启flash的websocket debug
        WEB_SOCKET_DEBUG = true;
        var ws, name, client_list={}, client_id, send_to_user_client_id='all',send_to_username='all';


        function connect(){
            ws = new WebSocket("ws://"+document.domain+":2346");
            ws.onopen = onopen;
            // 当有消息时根据消息类型显示不同信息
            ws.onmessage = onmessage;
            ws.onclose = function() {
                console.log("连接关闭，定时重连");
                connect();
            };
            ws.onerror = function() {
                console.log("出现错误");
            };
        }

        function onopen(){
            if(!name){
                show_prompt();
            }
            // 登录
            var login_data = '{"type":"login","client_name":"'+name.replace(/"/g, '\\"')+'","room_id":"<?php echo isset($_GET['room_id']) ? $_GET['room_id'] : 1?>"}';
            console.log("websocket握手成功，发送登录数据:"+login_data);
            ws.send(login_data);
            $("#user_name").text(name);
        }

        function onmessage(e){
            console.log(e.data);
            var data = JSON.parse(e.data);

            switch (data['type']){
                //服务端ping客户端
                case 'ping':
                    ws.send('{"type":"pong"}');
                    break;
                case 'login':
                    say(data['client_id'], data['client_name'], data['client_name']+' 加入了聊天室', data['time']);
                    if(data['client_list']){
                        client_list = data['client_list'];
                    }else{
                        client_list[data['client_id']] = data['client_name'];
                    }
                    flush_client_list();
                    console.log(data['client_name']+"登录成功");
                    break;
                case 'say':
                    say(data['from_client_id'], data['from_client_name'], data['content'], data['time']);
                    break;
                case 'logout':
                    say(data['from_client_id'],data['from_client_name'],data['from_client_name']+' 退出了',data['time']);
                    delete client_list[data['from_client_id']];
                    flush_client_list();
                    break;
                case 'init':
                    client_id = data['client_id'];
                    break;
            }
        }

        // 输入姓名
        function show_prompt(){
            name = prompt('输入你的名字：', '');
            if(!name || name=='null'){
                name = '游客';
            }
        }

        function say(from_client_id,from_client_name,content,time){
            if(from_client_id == client_id){
                $("#msg_list_ul").append(
                    '<li class="clearfix"><div class="message-data align-right"><span class="message-data-time" >'+time+'</span> &nbsp; &nbsp;<span class="message-data-name" >'+from_client_name+'</span> <i class="fa fa-circle me"></i></div><div class="message other-message float-right">'+content+'</div></li>'
                )
            }else{
                $("#msg_list_ul").append(
                    '<li><div class="message-data"><span class="message-data-name"><i class="fa fa-circle online"></i>'+from_client_name+'</span><span class="message-data-time">'+time+'</span></div><div class="message my-message">'+content+'</div></li>'
                )
            }

            document.getElementById("chat-history-div").scrollTop=document.getElementById("chat-history-div").scrollHeight;

        }

        function flush_client_list(){
            var client_list_ul = $("#client_list_ul");
            var img = '{{ asset("img/t1.png") }}';
            client_list_ul.empty();
            client_list_ul.append(
                '<li class="clearfix" onclick="click_user(this)" id="all"><img src="'+img+'" alt="avatar" /><div class="about"><div class="name">all</div><div class="status"><i class="fa fa-circle online"></i> online</div></div></li>'
            );
            for(var p in client_list){
                //client_list_ul.append('<li id="'+p+'">'+client_list[p]+'</li>')
                client_list_ul.append(
                    '<li class="clearfix" onclick="click_user(this)" id="'+p+'"><img src="'+img+'" alt="avatar" /><div class="about"><div class="name">'+client_list[p]+'</div><div class="status"><i class="fa fa-circle online"></i> online </div></div></li>'
                );
            }
        }

        function submit(){
            var input = $("#message-to-send");
            var input_text = input.val();
            var to_client_id = send_to_user_client_id;
            var to_client_name = send_to_username;
            ws.send('{"type":"say","to_client_id":"'+to_client_id+'","to_client_name":"'+to_client_name+'","content":"'+input_text.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')+'"}');

            input.val('');
            input.focus();
        }

        function click_user(e){
            var user_client_id = $(e).attr('id');
            var user_name = $(e).children('div').children('.name').text();

            if(client_id == user_client_id){
                return;
            }
            send_to_user_client_id = user_client_id;
            send_to_username = user_name;

            $("#message-to-send").attr('placeholder','send to '+user_name);

        }
    </script>
</head>
<body onload="connect()">
<!-- partial:index.partial.html -->
  <div class="container clearfix">
    <div class="people-list" id="people-list">
      <div class="search">
        <input type="text" placeholder="search" />
        <i class="fa fa-search"></i>
      </div>
{{--        在线用户列表--}}
      <ul class="list" id="client_list_ul">
{{--        <li class="clearfix" onclick="click_user(this)" id="all">--}}
{{--          <img src="{{ asset('img/t1.png') }}" alt="avatar" />--}}
{{--          <div class="about">--}}
{{--            <div class="name">all</div>--}}
{{--            <div class="status">--}}
{{--              <i class="fa fa-circle online"></i> online--}}
{{--            </div>--}}
{{--          </div>--}}
{{--        </li>--}}
      </ul>
    </div>

    <div class="chat">
{{--        用户信息--}}
      <div class="chat-header clearfix">
        <img src="{{ asset('img/t1.png') }}" alt="avatar" />

        <div class="chat-about">
          <div class="chat-with" id="user_name"></div>
          <div class="chat-num-messages">already 1 902 messages</div>
        </div>
        <i class="fa fa-star"></i>
      </div> <!-- end chat-header -->
{{--        聊天列表--}}
      <div class="chat-history" id="chat-history-div">
        <ul id="msg_list_ul">
{{--          <li class="clearfix">--}}
{{--            <div class="message-data align-right">--}}
{{--              <span class="message-data-time" >10:10 AM, Today</span> &nbsp; &nbsp;--}}
{{--              <span class="message-data-name" >Olia</span> <i class="fa fa-circle me"></i>--}}

{{--            </div>--}}
{{--            <div class="message other-message float-right">--}}
{{--              Hi Vincent, how are you? How is the project coming along?--}}
{{--            </div>--}}
{{--          </li>--}}

{{--            正在输入--}}
{{--          <li>--}}
{{--            <div class="message-data">--}}
{{--              <span class="message-data-name"><i class="fa fa-circle online"></i> Vincent</span>--}}
{{--              <span class="message-data-time">10:31 AM, Today</span>--}}
{{--            </div>--}}
{{--            <i class="fa fa-circle online"></i>--}}
{{--            <i class="fa fa-circle online" style="color: #AED2A6"></i>--}}
{{--            <i class="fa fa-circle online" style="color:#DAE9DA"></i>--}}
{{--          </li>--}}

        </ul>

      </div> <!-- end chat-history -->

      <div class="chat-message clearfix" id="msg_text">
          <a href="{{url('test/chat?room_id=1')}}">房间1</a> &nbsp; <a href="{{url('test/chat?room_id=2')}}">房间2</a> &nbsp; <a href="{{url('test/chat?room_id=3')}}">房间3</a>
          <textarea name="message-to-send" id="message-to-send" placeholder ="" rows="3"></textarea>

        <i class="fa fa-file-o"></i> &nbsp;&nbsp;&nbsp;
        <i class="fa fa-file-image-o"></i>

        <button onclick="submit()">发送</button>

      </div> <!-- end chat-message -->

    </div> <!-- end chat -->

  </div> <!-- end container -->

<!-- partial -->
<script src='{{ asset('js/list.min.js') }}'></script>

<script type="text/javascript">
    // 动态自适应屏幕
    document.write('<meta name="viewport" content="width=device-width,initial-scale=1">');
    $("#message-to-send").on("keydown", function(e) {
        // 按enter键自动提交
        if(e.keyCode === 13 && !e.ctrlKey) {
            e.preventDefault();
            submit();
            return false;
        }

        // 按ctrl+enter组合键换行
        if(e.keyCode === 13 && e.ctrlKey) {
            $(this).val(function(i,val){
                return val + "\n";
            });
        }
    });
</script>

</body>
</html>
