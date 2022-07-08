<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class GUserController extends Controller
{
    public function fetchUsers()
    {
        $statusCode = 404;
        $users = Redis::get('kjo:users');

        if (empty($users)) {
            $endpoint = "http://server.ashari.me:3031/api/management/users/spreadsheet";
            $client = new \GuzzleHttp\Client();

            $response = $client->request('GET', $endpoint);

            $statusCode = $response->getStatusCode();

            $content = json_decode($response->getBody(), true);
            if ($statusCode == 200) {
                Redis::set('kjo:users', $response->getBody());
            }
            return $content;
        } else {
            return json_decode($users, true);
        }
        return $statusCode;

    }
}
