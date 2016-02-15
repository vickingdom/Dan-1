<?php

use Dan\Contracts\UserContract;
use Dan\Irc\Connection;
use Dan\Irc\Location\Channel;
use Dan\Irc\Location\User;

command(['admins', 'owners', 'owner'])
    ->allowPrivate()
    ->rank('*')
    ->helpText('Lists bot admins and owners')
    ->handler(function (Connection $connection, UserContract $user, Channel $channel = null) {
        $method = $channel ? 'notice' : 'message';

        $users = $connection->database('users')->get();
        $owners = [];
        $admins = [];

        foreach ($users as $user) {
            $user = new User($connection, $user);

            if ($connection->isOwner($user)) {
                $owners[] = $user->nick;
            }

            if ($connection->isAdmin($user)) {
                $admins[] = $user->nick;
            }
        }

        $user->$method('Owners: '.implode(', ', $owners));
        $user->$method('Admins: '.implode(', ', $admins));
    });
