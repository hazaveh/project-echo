<?php

use App\Console\Commands\Reddit\RedditNewPostsGrabberCommand;
use App\Console\Commands\Reddit\RedditUserGrabberCommand;

Schedule::command(RedditNewPostsGrabberCommand::class, ['postrock'])->daily();
Schedule::command(RedditUserGrabberCommand::class)->daily();
