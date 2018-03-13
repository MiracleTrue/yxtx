<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Storage;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /*每分钟执行一次任务*/
        $schedule->command('NoPaymentRegistrationDelete')->everyMinute();/*未付款报名,15分钟后删除 (Artisan 计划任务)*/

        /*每半小时执行一次任务*/
        $schedule->command('HandleEndMatch')->everyThirtyMinutes();/*处理已过结束时间的比赛,改为已结束(Artisan 计划任务)*/

        /*每天午夜执行一次任务*/
        $schedule->command('WxAppKeyClear')->daily();/*清除过期的WxAppKey(Artisan 计划任务)*/

//        $schedule->call(function () {
//            $prefix_path = Storage::disk('local')->getAdapter()->getPathPrefix();
//
//            $a = new File($prefix_path . 'thumb/201710/4/4MXHPAO6cwbbtPIVPoYGWoxhImDQlW3tDorS6PPJ.jpeg');
//            $path = Storage::disk('local')->putFileAs('temp', $a, date('H-i-s',time()) . '.jpeg');
//        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
