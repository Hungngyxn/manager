<?php

namespace App\Console;

use App\Console\Commands\AttendanceAbsent;
use App\Console\Commands\AttendanceLeave;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        AttendanceAbsent::class,
        AttendanceLeave::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('tiktok:get-labels')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()

            ->before(function () {
                \Log::info('Cron TikTok: Bắt đầu chạy lúc ' . now());
            })
            ->after(function () {
                \Log::info('Cron TikTok: Kết thúc lúc ' . now());
            })
            ->onFailure(function () {
                \Log::error('Cron TikTok: Lỗi khi chạy command');
            });
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
