<?php

namespace App\Console\Commands\Reddit;

use App\Models\Reddit\RedditPost;
use App\Models\Reddit\RedditUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RedditNewPostsGrabberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reddit:new {subreddit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the new posts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = "https://oauth.reddit.com/r/{$this->argument('subreddit')}/new.json";

        $response = Http::reddit()->get($url);

        if ($response->failed()) {
            $this->error('Error: '.$response->status());

            return Command::FAILURE;
        }

        foreach ($response->json('data.children') as $post) {
            $this->info("Saving Post: {$post['data']['id']}");

            RedditPost::firstOrCreate([
                'username' => $post['data']['author'],
                'post_id' => $post['data']['id'],
            ]);

            RedditUser::firstOrCreate([
                'username' => $post['data']['author'],
            ]);
        }

        $this->info('Posts have been saved.');
    }
}
