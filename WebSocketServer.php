<?php

/**
 * Created by PhpStorm.
 * User: wy220
 * Date: 2018/6/28
 * Time: 21:43
 */
class WebSocketServer
{
    protected  $server;
    protected  $swoole_table;
    public function __construct()
    {
        $this->createTable();
        //创建websocket对象，监听9528端口
        $this->server = new swoole_websocket_server('0.0.0.0',9528);

        $this->server->on('open',function (swoole_websocket_server $server,$request){
            $user_num = $request->get['user_num'];
            $fd  = $request->fd;
            $this->swoole_table->set("$user_num",array('fd'=>$fd));
            echo "client user_num is :{$request->get['user_num']}\n";
            echo "server:im server ! fd is {$request->fd} \n";
            $table_fd = $this->swoole_table->get($user_num)['fd'];
            echo "table memory fd is:{$table_fd}\n";
        });

        $this->server->on('message',function (swoole_websocket_server $server,$frame){
            echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish} \n";
            $send_to_user_num = json_decode($frame->data,true)['send_to_user_num'];
            $send_to_user_message = json_decode($frame->data,true)['message'];

            $send_to_user_fd = $this->swoole_table->get($send_to_user_num)['fd'];

            $server->push($send_to_user_fd,$send_to_user_message);
        });


        $this->server->on('close',function (swoole_websocket_server $server,$fd) {
            echo "client{$fd} closed!!!\n";
        });

        $this->server->start();
    }

    public function createTable(){
        $this->swoole_table = new swoole_table(1024);
        $this->swoole_table->column('fd',swoole_table::TYPE_INT,8);
        $this->swoole_table->create();
    }
}
new WebSocketServer();