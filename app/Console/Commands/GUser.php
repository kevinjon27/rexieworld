<?php

namespace App\Console\Commands;

use App\Http\Controllers\GUserController;
use Illuminate\Console\Command;

class GUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch user from google sheet';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = new GUserController();
        $result = $user->fetchUsers();

        echo json_encode($result);
    }
}
