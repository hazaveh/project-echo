<?php

namespace App\Console\Commands;

use App\Mail\SampleTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSampleMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-sample-mail {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send sample mail';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        Mail::raw('Hello World!', function ($message) use ($email) {
            $message->to($email);
            $message->subject('Hello World!');
        });
    }
}
