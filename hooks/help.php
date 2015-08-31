<?php

use Dan\Hooks\HookManager;
use Illuminate\Support\Collection;

hook('help')
    ->command(['help'])
    ->console()
    ->help('Gets help')
    ->func(function(Collection $args) {
        $hooks = HookManager::getHooks('command');
        $name = $args['message'];

        $list = [];

        foreach($hooks as $hook)
        {
            $cmds = $hook->hook()->commands;

            if($name != null && in_array($name, $cmds))
            {
                $args['channel']->message($hook->hook()->help);
                return;
            }

            $first = array_shift($cmds);

            $cmd = $first . (count($cmds) > 0 ? " (" . implode(', ', $cmds) . ")" : '');

            $list = array_merge($list, (array)$cmd);
        }

        sort($list);

        $args['channel']->message(implode(', ', $list));
    });