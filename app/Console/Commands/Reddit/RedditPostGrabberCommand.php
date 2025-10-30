<?php

namespace App\Console\Commands\Reddit;

use App\Models\Reddit\RedditPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RedditPostGrabberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reddit:posts {after?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Posts!';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $after = $this->argument('after') ?? true;
        $url = "https://www.reddit.com/r/postrock/top.json";

        while($after != null) {
            $this->info("Fetching Post after: $after");
            $response = Http::get($url, [
                'limit' => 100,
                't' => 'all',
                'after' => $after
            ]);


            $after = $response->json('data.after');

            if ($response->failed()) {
                $this->error("Error: {$response->status()} Last Post: $after");
            }

            foreach ($response->json()['data']['children'] as $post) {
                $this->info("Saving Post: {$post['data']['id']}");
                RedditPost::create([
                    'username' => $post['data']['author'],
                    'post_id' => $post['data']['id'],
                ]);
            }
            sleep(2);
        }
    }
}
