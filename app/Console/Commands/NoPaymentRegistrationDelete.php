<?php

namespace App\Console\Commands;

use App\Entity\MatchRegistration;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 未付款报名,15分钟后删除 (Artisan 计划任务)
 * Class HandleOverdueOffer
 * @package App\Console\Commands
 */
class NoPaymentRegistrationDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'NoPaymentRegistrationDelete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '未付款报名,15分钟后删除 (Artisan 计划任务)';

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
        MatchRegistration::where('status', Registration::STATUS_WAIT_PAYMENT)->where('create_time', '<', now()->subMinute(15))->delete();
    }
}
