<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Notifications\UserReservationStarting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDueReservationsNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-due-reservations-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Reservation::query()
            ->with('office.user') //Prevent n+1 problem
            ->where('status', Reservation::STATUS_ACTIVE)
            ->where('start_date', now()->toDateString())
            ->each(function($reservation) {
                Notification::send($reservation->user, new UserReservationStarting($reservation));
            });
        //
    }
}
