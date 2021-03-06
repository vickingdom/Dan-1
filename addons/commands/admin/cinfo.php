<?php


use Dan\Irc\Connection;
use Dan\Irc\Location\Channel;
use Dan\Irc\Location\User;
use Illuminate\Support\Pluralizer;

command(['chaninfo', 'cinfo'])
    ->rank('oaq')
    ->helpText([
        'cinfo hooks disabled - Lists disabled hooks',
        'cinfo hooks enable <hook> - Enables a hooks',
        'cinfo hooks disable <hook> - Disables a hooks',
        'cinfo hooks settings <hook> <setting> [value] - Gets or sets a setting for the hook.',
        'cinfo commands disabled - Lists disabled commands',
        'cinfo commands enable <command> - Enables a command',
        'cinfo commands disable <command> - Disables a command',
    ])
    ->handler(new class {

        /**
         * @var Connection
         */
        protected $connection;

        /**
         * @param \Dan\Irc\Connection $connection
         * @param \Dan\Irc\Location\Channel $channel
         * @param \Dan\Irc\Location\User $user
         * @param $message
         *
         * @return bool
         */
        public function run(Connection $connection, Channel $channel, User $user, $message)
        {
            $this->connection = $connection;

            if (empty($message)) {
                return false;
            }

            $data = explode(' ', $message);
            $name = $data[0];
            $method = 'type' . ucfirst(strtolower($name));

            if (method_exists($this, $method)) {
                array_shift($data);
                return $this->$method($channel, $user, $data);
            }

            $channel->message("Invalid command {$name}");

            return null;
        }

        /**
         * @param \Dan\Irc\Location\Channel $channel
         * @param \Dan\Irc\Location\User $user
         * @param array $data
         *
         * @return bool|null
         */
        public function typeHooks(Channel $channel, User $user, array $data)
        {
            if ($data[0] == 'disabled') {
                $list = $channel->getData('info.hooks.disabled', []);
                $user->notice('Disabled hooks: '.implode(', ', $list))->save();

                return null;
            }

            if (!isset($data[1])) {
                $user->notice('Please specify a hook.');

                return null;
            }

            $hooks = explode(',', $data[1]);
            $return = null;

            foreach ($hooks as $hook) {
                $hook = trim($hook);

                if ($data[0] == 'enable') {
                    $return = $this->doThing($channel, $user, 'hook', $hook);
                }

                if ($data[0] == 'disable') {
                    $return = $this->doThing($channel, $user, 'hook', $hook, false);
                }
            }

            if ($return) {
                return $return;
            }

            if ($data[0] == 'settings') {
                if (!isset($data[2])) {
                    $user->notice('Please specify a setting key.');

                    return null;
                }

                if (!$channel->getData("hooks.{$data[1]}")) {
                    $user->notice('There are no settings for this hook.');

                    return null;
                }

                if (!$channel->getData("hooks.{$data[1]}.{$data[2]}")) {
                    $user->notice("The setting key <i>{$data[2]}</i> doesn't exist.");

                    return null;
                }

                if (isset($data[3])) {
                    $value = explode(',', $data[3]);
                    $options = $channel->getData("hooks.{$data[1]}.{$data[2]}.options");

                    foreach ($value as $option) {
                        $option = trim($option);

                        if (!in_array($option, $options)) {
                            $user->notice("Invalid option {$option}. See <i>hooks settings {$data[1]} {$data[2]}.options</i> for a list of available options.");

                            return null;
                        }
                    }

                    $channel->setData("hooks.{$data[1]}.{$data[2]}.default", $data[3])
                        ->message("Settings saved.")
                        ->save();

                    return null;
                }

                if (last(explode('.', $data[2])) == 'options') {
                    $options = $channel->getData("hooks.{$data[1]}.{$data[2]}");
                    $user->notice("Options for {$data[2]}: ".implode(', ', $options));

                    return null;
                }

                $user->notice("Current value for {$data[2]}: ".$channel->getData("hooks.{$data[1]}.{$data[2]}.default"));
            }
        }

        /**
         * @param \Dan\Irc\Location\Channel $channel
         * @param \Dan\Irc\Location\User $user
         * @param array $data
         *
         * @return bool|void
         */
        public function typeCommands(Channel $channel, User $user, array $data)
        {
            if ($data[0] == 'disabled') {
                $list = $channel->getData('info.commands.disabled', []);
                $user->notice('Disabled commands: '.implode(', ', $list))->save();

                return null;
            }

            if (!isset($data[1])) {
                $user->notice('Please specify a command.');

                return null;
            }

            if ($data[0] == 'enable') {
                return $this->doThing($channel, $user, 'command', $data[1]);
            }

            if ($data[0] == 'disable') {
                return $this->doThing($channel, $user, 'command', $data[1], false);
            }

            $user->notice("Invalid command {$data[0]}.");
        }

        /**
         * @param \Dan\Irc\Location\Channel $channel
         * @param \Dan\Irc\Location\User $user
         * @param $type
         * @param $name
         * @param bool $enable
         *
         * @return bool
         */
        public function doThing(Channel $channel, User $user, $type, $name, $enable = true)
        {
            $plual = Pluralizer::plural($type);

            $method = $enable ? 'forgetData' : 'putData';
            $what = $enable ? 'enabled' : 'disabled';
            $type = ucfirst($type);

            $disabled = $channel->getData("info.{$plual}.disabled", []);

            $in = in_array($name, $disabled);
            $in = $enable ? !$in : $in;

            if ($in) {
                $user->notice("{$type} {$name} is already {$what}.");

                return true;
            }

            $channel->$method("info.{$plual}.disabled", $name)->save();
            $user->notice("{$type} <b>{$name}</b> has been {$what}.");

            return true;
        }
    });

