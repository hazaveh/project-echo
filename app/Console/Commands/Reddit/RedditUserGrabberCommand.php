<?php

namespace App\Console\Commands\Reddit;

use App\Models\Reddit\RedditPost;
use App\Models\Reddit\RedditUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RedditUserGrabberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reddit:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach (RedditPost::where('is_processed', '0')->take(10)->get() as $post) {
            $this->warn("Fetching Comments for Post: {$post->post_id}");

            $response = Http::reddit()->get("https://oauth.reddit.com/r/postrock/comments/{$post->post_id}.json", ['limit' => 100]);

            if (!is_array($response->json('1.data.children'))) {
                $this->error("Error: {$response->status()} Last Post: {$post->post_id}");
                if ($response->status() == 429) {
                    dd("Rate Limit");
                }
                continue;
            }
            foreach ($response->json('1.data.children') as $comment) {
                try {
                    $this->info("Saving User: {$comment['data']['author']}");
                    RedditUser::firstOrCreate(['username' => $comment['data']['author']]);
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $post->update(['is_processed' => '1']);
            sleep(rand(1,8));
        }
    }
}
