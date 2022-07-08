<?php

namespace App\Console\Commands;

use App\Http\Controllers\GUserController;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Http\Controllers\WaController;

class NotifBill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notif:bill {tagihan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notification billing';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function     __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return boolean
     */
    public function handle()
    {
        $data = [];
        $tagihan = $this->argument('tagihan');
        $wa = new WaController();
        if(isset($tagihan)) {
            $users = new GUserController();
            $users = $users->fetchUsers();


            foreach($users as $user) {
                if (Carbon::parse($user['memberships'][0]['end_date'])->format('m') == Carbon::now()->format('m')) {
                    if(Carbon::now()->diffInDays($user['memberships'][0]['end_date'], false) < 15) {
//                        if ($user['phone_number'] == '6281213931807') {
//
//                        }
                        $text = 'Hai *'. $user['full_name'] .'*, kamu ada tagihan di *bulan* ini.';
                        $wa->send($user['phone_number'], $text);
                        sleep(1);
                        $wa->send('6287808788565', $text);
                        sleep(1);
                        $wa->send('6281213931807', $text);

                        $text = sprintf(__('messages.tagihan'),
                            Carbon::now()->diffInDays($user['memberships'][0]['end_date'], false),
                            Carbon::parse($user['memberships'][0]['end_date'])->isoFormat('MMM Do YY'),
                            $user['memberships'][0]['price']['currency'],
                            $this->rupiah($user['memberships'][0]['price']['value'])
                        );

                        $wa->send($user['phone_number'], $text);
                        sleep(1);
                        $wa->send('6287808788565', $text);
                        sleep(1);
                        $wa->send('6281213931807', $text);

                        echo 'Hai *'. $user['full_name'] .'*, kamu ada tagihan di *bulan* ini.';
                        echo $text;
                        echo "-----------------\n";
                        sleep(3);
                    }
                    else if(Carbon::now()->diffInDays($user['memberships'][0]['end_date'], false) == 0){
//                        if ($user['phone_number'] == '6281213931807') {
//
//                        }
                        $text = 'Hai *'. $user['full_name'] .'*, kamu ada tagihan *hari ini*.';
                        $wa->send($user['phone_number'], $text);
                        sleep(1);
                        $wa->send('6287808788565', $text);
                        sleep(1);
                        $wa->send('6281213931807', $text);

//
                        if (Carbon::now()->diffInDays($user['memberships'][0]['end_date'], false) == 0) {
                            $remaining_day = 'today expired';
                        } else {
                            $remaining_day = Carbon::now()->diffInDays($user['memberships'][0]['end_date'], false) . 'day';
                        }

                        $text = sprintf(__('messages.tagihan'),
                            $remaining_day,
                            Carbon::parse($user['memberships'][0]['end_date'])->isoFormat('MMM Do YY'),
                            $user['memberships'][0]['price']['currency'],
                            $this->rupiah($user['memberships'][0]['price']['value'])
                        );


                        $wa->send($user['phone_number'], $text);
                        sleep(1);
                        $wa->send('6287808788565', $text);
                        sleep(1);
                        $wa->send('6281213931807', $text);
                        echo 'Hai *'. $user['full_name'] .'*, kamu ada tagihan *hari ini*.';
                        echo $text;
                        echo "-----------------\n";
                    }
                }
            }

        }
        return null;
    }


    function rupiah($angka){

        $hasil_rupiah = number_format($angka,0,',','.');
        return $hasil_rupiah;

    }
}
