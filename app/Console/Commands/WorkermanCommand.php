<?php

/**
 * Created by phpstorm.
 * User: 陈伟权
 * Date: 2021/4/13
 * Time: 16:08
 */
namespace App\Console\Commands;

use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use Illuminate\Console\Command;
use Workerman\Worker;

class WorkermanCommand extends Command{

    //兼容win
    protected $signature = 'workerman
                            {action : action}
                            {--start=all : start}
                            {--d : daemon mode}';

    //win环境屏蔽
    //protected $signature = 'workman {action} {--d}';

    protected $description = 'Start a Workerman server.';

    public function handle()
    {
        global $argv;
        $action = $this->argument('action');

        //针对 Windows 一次执行，无法注册多个协议的特殊处理
        if ($action === 'single') {
            $start = $this->option('start');
            if ($start === 'register') {
                $this->startRegister();
            } elseif ($start === 'gateway') {
                $this->startGateWay();
            } elseif ($start === 'worker') {
                $this->startBusinessWorker();
            }
            Worker::runAll();

            return;
        }

        //使用win环境时屏蔽
        //$argv[0] = 'wk';
        $argv[1] = $action;
        $argv[2] = $this->option('d') ? '-d' : '';

        $this->start();
    }

    private function start()
    {
        $this->startGateWay();
        $this->startBusinessWorker();
        $this->startRegister();
        Worker::runAll();
    }

    private function startBusinessWorker()
    {
        $worker                  = new BusinessWorker();
        $worker->name            = 'BusinessWorker';
        $worker->count           = 1;
        $worker->registerAddress = '127.0.0.1:1236';
        $worker->eventHandler    = \App\Workerman\Events::class;
    }

    private function startGateWay()
    {
        $gateway = new Gateway("websocket://0.0.0.0:2346");
        $gateway->name                 = 'Gateway';
        $gateway->count                = 1;
        $gateway->lanIp                = '127.0.0.1';
        $gateway->startPort            = 2300;
        $gateway->pingInterval         = 30;
        $gateway->pingNotResponseLimit = 0;
        $gateway->pingData             = '{"type":"ping"}';
        $gateway->registerAddress      = '127.0.0.1:1236';
    }


    private function startRegister()
    {
        new Register('text://0.0.0.0:1236');
    }
}
