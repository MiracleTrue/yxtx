<?php

namespace App\Console\Commands;

use App\Entity\WxAppkey;
use Illuminate\Console\Command;

/**
 * 清除过期的WxAppKey(Artisan 计划任务)
 * Class HandleOverdueOffer
 * @package App\Console\Commands
 */
class WxAppKeyClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'WxAppKeyClear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除过期的WxAppKey(Artisan 计划任务)';

    /**
     * HandleOverdueOffer constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*初始化*/
        WxAppkey::where('valid_time', '<', now())->delete();
    }
}
