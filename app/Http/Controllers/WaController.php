<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class WaController extends Controller
{
    public function callback(Request $request)
    {
        $json = $request->all();
        Log::info($json);

        if (isset($json['ischat'])) {
            $wit = $this->witAI($json['message']);

            if(isset($wit->intents)) {
                $intent = $wit->intents[0];
                if($intent->confidence > 0.8) {
                    switch($intent->name) {
                        case 'profile':
                            $this->sendProfile($json);
                            break;
                        case 'payment':
                            $this->sendPayment($json);
                            break;
                    }
                } else {
                    echo 'false 1';
                }
            } else {
                echo 'false 2';
            }
        }


        return null;
    }

    public function findUser($users, $find)
    {
        foreach($users as $user) {
            if(Str::contains($find, $user['phone_number'])) {
                return $user;
            }
        }
        return null;
    }

    public function send($phone_number, $message)
    {
        $endpoint = "/api/send_message";

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => env('WA_API') . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'token='.env('WA_TOKEN').'&number='. $phone_number .'&message=' .$message. '&messageid=2EFD576575BF1741C3530xxxxxxxxx',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    public function witAI($message)
    {
        $client = new Client();
        $endpoint = env('WIT_API') . urlencode($message);

        $ch = curl_init();
        $header = array();
        $header[] = "Authorization: Bearer ".  env('WIT_TOKEN');

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header); //sets the header value above - required for wit.ai authentication
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //inhibits the immediate display of the returned data

        $server_output = curl_exec ($ch); //call the URL and store the data in $server_output

        curl_close ($ch);  //close the connection

        return json_decode($server_output);
    }

    public function sendProfile($json)
    {
        $users = new GUserController();
        $users = $users->fetchUsers();
        $phone_number = "";

        if ($json['category'] == 'group') {
            $user = $this->findUser($users, $json['participantnumber']);
            $phone_number = $json['number'];
        } else if ($json['category'] == 'private'){
            $user = $this->findUser($users, $json['number']);
            $phone_number = $json['number'];
        }

        if ($user) {
            $remaining_day = '';
            if (Carbon::now()->diffInDays($user['memberships'][0]['end_date'], false) == 0) {
                $remaining_day = 'today expired';
            } else {
                $remaining_day = Carbon::now()->diffInDays($user['memberships'][0]['end_date'], false) . 'day';
            }
            $text = sprintf(__('messages.profile'),
                $user['full_name'],
                $user['phone_number'],
                $user['email'],
                $user['memberships'][0]['type'],
                Carbon::parse($user['memberships'][0]['end_date'])->isoFormat('MMM Do YY'),
                $remaining_day,
                $user['memberships'][0]['duration_months'],
                $user['memberships'][0]['price']['currency'],
                $this->rupiah($user['memberships'][0]['price']['value'])
            );

            $this->send($user['phone_number'], $text);
        } else {
            $this->send($phone_number, "Maaf, nomor anda tidak terdaftar di KJo's database");
            echo "Maaf, nomor anda tidak terdaftar di KJo's database";
        }
    }

    public function sendPayment($json)
    {
        $users = new GUserController();
        $users = $users->fetchUsers();
        $phone_number = "";

        if ($json['category'] == 'group') {
            $user = $this->findUser($users, $json['participantnumber']);
            $phone_number = $json['number'];
        } else if ($json['category'] == 'private'){
            $user = $this->findUser($users, $json['number']);
            $phone_number = $json['number'];
        }
        $remaining_day = '';
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

        $this->send($user['phone_number'], $text);
    }

    function rupiah($angka){

        $hasil_rupiah = number_format($angka,0,',','.');
        return $hasil_rupiah;

    }
}
