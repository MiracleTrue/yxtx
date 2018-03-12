<?php

namespace App\Console\Commands;

use App\Entity\MatchList;
use App\Models\Match;
use Illuminate\Console\Command;

/**
 * 处理已过结束时间的比赛,改为已结束(Artisan 计划任务)
 * Class HandleOverdueOffer
 * @package App\Console\Commands
 */
class HandleEndMatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'HandleEndMatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '处理已过结束时间的比赛,改为已结束(Artisan 计划任务)';

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
        MatchList::whereIn('status', [Match::STATUS_SIGN_UP, Match::STATUS_GET_NUMBER])->where('match_end_time', '<', now()->subHours(12))
            ->update(['status' => Match::STATUS_END]);
    }
}
