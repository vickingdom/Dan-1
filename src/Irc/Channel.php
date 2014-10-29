<?php namespace Dan\Irc; 


use Dan\Events\Event;

class Channel {

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var int
     */
    protected $titleDate;

    /**
     * @var string
     */
    protected $titleSetter;

    /**
     * @var array
     */
    protected $users = [];

    /**
     * @param $connection
     * @param $name
     */
    public function __construct(Connection &$connection, $name)
    {
        $this->setName($name);
        $this->connection = $connection;

        Event::listen('irc.packet.mode', function($data)
        {
            $this->connection->sendRaw("NAMES {$this->name}");
        });
    }

    /**
     * Clears the user list.
     */
    public function clearUsers()
    {
        $this->users = [];
    }

    /**
     * Gets the channel users.
     *
     * @return array
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Sets the channel name.
     *
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the channel title.
     *
     * @param $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param User[] $names
     */
    public function setNames($names)
    {
        foreach($names as $user)
            $this->users[$user->getNick()] = $user->getRank();
    }

    /**
     * Sets the title information
     *
     * @param $user
     * @param $date
     */
    public function setTitleInfo($user, $date)
    {
        $this->titleSetter  = $user;
        $this->titleDate    = $date;
    }

    /**
     * Sends a message to the channel.
     *
     * @param $message
     */
    public function sendMessage($message)
    {
        $this->connection->sendMessage($this->name, $message);
    }
}
 