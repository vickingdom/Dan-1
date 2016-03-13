<?php

use Dan\Web\Request;
use Endroid\Twitter\Twitter;
use Illuminate\Support\Collection;

route('twitter.update')
    ->config([
        'access' => [
            'api_key' => '',
            'api_secret' => '',
            'access_token' => '',
            'access_secret' => '',
            'api_url' => '',
            'proxy' => '',
            'timeout' => 5,
        ],
        'users' => [
            'name'  => 'uclcommander',
            'replies' => true,
            'network' => 'byteirc',
            'channel' => '#example',
        ],
    ])
    ->path('/twitter/update')
    ->get(function(Request $request) {
        $access = config('twitter_update.access');
        $users = config('twitter_update.users');

        $twitter = new Twitter(...$access);

        $db = database()->table('cache')->where('key', 'twitter_posted_tweets');
        $cache = $db->first()->get('value');

        if($db->count() == 0) {
            $db->insert(['key' => 'twitter_posted_tweets', 'value' => []]);
            $cache = [];
        }

        foreach($users as $user) {
            $data = $twitter->getTimeline([
                'screen_name'       => $user['name'],
                'count'             => 3,
                'exclude_replies'   => !($user['replies'] ?? true)
            ]);

            if (count($data) == 0) {
                continue;
            }

            foreach ($data as $tweet) {
                if (in_array($data->id_str, $cache)) {
                    continue;
                }

                if (!connection()->hasConnection($user['network'])) {
                    continue;
                }

                /** @var \Dan\Irc\Connection $connection */
                $connection = connection($user['network']);

                if (!$connection->inChannel($user['channel'])) {
                    continue;
                }

                $text = htmlspecialchars_decode(cleanString($data->text), ENT_QUOTES);
                $link = shortLink("https://twitter.com/{$tweet->user->screen_name}/status/{$tweet->id_str}");

                $connection->getChannel($user['channel'])
                    ->message("[ Twitter ] <cyan>@{$tweet->user->screen_name}:</cyan> {$text} - {$link}");

                $cache[] = $tweet->id_str;
            }
        }

        $db->update(['value' => $cache]);
    });