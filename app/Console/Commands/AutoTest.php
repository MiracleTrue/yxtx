<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Artisan 自定义测试
 * Class AutoTest
 * @package App\Console\Commands
 */
class AutoTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AutoTest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test';

    /**
     * AutoTest constructor.
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
        $prefix_path = Storage::disk('local')->getAdapter()->getPathPrefix();
        $file = new File($prefix_path . 'thumb/201710/4/4MXHPAO6cwbbtPIVPoYGWoxhImDQlW3tDorS6PPJ.jpeg');
        $path = Storage::disk('local')->putFileAs('temp', $file, date('H-i-s',time()) . '.jpeg');

        Log::info($path);
    }
}
