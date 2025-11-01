<?php

use App\Console\Commands\Reddit\RedditNewPostsGrabberCommand;
use App\Console\Commands\Reddit\RedditUserGrabberCommand;
use App\Console\Commands\Reddit\SendRedditMessageCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(RedditNewPostsGrabberCommand::class, ['postrock'])->daily();
Schedule::command(RedditUserGrabberCommand::class)->days(2);
Schedule::command(SendRedditMessageCommand::class)->hourly();
