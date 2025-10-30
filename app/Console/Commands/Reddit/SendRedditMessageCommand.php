<?php

namespace App\Console\Commands\Reddit;

use App\Models\Reddit\RedditUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SendRedditMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reddit:message';

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
        foreach(RedditUser::where('contacted', false)->take(100)->get() as $user) {
            $response = Http::reddit()->post('https://oauth.reddit.com/api/compose', [
                'api_type' => 'json',
                'to' => $user->username,
                'subject' => 'Check out Post-Rock Nation',
                'text' => $this->message($user->username),
            ]);

            if ($response->failed()) {
                $this->error("Error: {$response->status()} Last Post: {$user->username}");
            }

            $user->contacted = true;
            $user->save();
            sleep(rand(1,15));
            $this->info("sent message to {$user->username}");
        }
    }

    public function message($username): string
    {
        $messages = [];
        $messages[] = <<<MSG
                    Hey {$username},

                    I’m saw you’re into post-rock too, just wanted to share something that might interest you.

                    There’s a little radio station we’ve been running at Post-Rock Nation, a small project focused on helping people discover great post-rock. It plays 24/7.

                    If you ever feel like letting it run in the background:
                    https://postrocknation.com/go/radio

                    Hope it adds something to your day.
                    Mahdi
                    MSG;

        $messages[] = <<<MSG
                    Hey {$username},

                    I saw you're into post-rock and thought you might appreciate this, I've been helping out with a project called Post-Rock Nation. It's a space to discover bands, playlists, and other music we’re excited about.

                    we also have an 24x7 radio station, a community forum and lots of other cool stuff.

                    it would be great if you could check it out: https://postrocknation.com/go/explore

                    Would honestly love to hear what you think or if anything’s missing.

                    Take care,
                    Mahdi
                    MSG;

        $messages[] = <<<MSG
                    Hey {$username},

                    I came across your interest in post-rock and wanted to share something we’re doing. Post-Rock Nation’s a small project trying to surface new music in the genre, and some artists have generously provided Bandcamp codes for one of their albums.

                    If you're up for it, you can grab a code here:
                    https://postrocknation.com/go/free-album

                    Let me know what you think if you give it a spin.

                    Cheers,
                    Mahdi
                    MSG;

        return $messages[array_rand($messages)];
    }
}
