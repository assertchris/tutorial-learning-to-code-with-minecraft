<?php

class Game
{
    /**
     * @var string
     */
    public $player;

    /**
     * @var int
     */
    public $coder;

    /**
     * @param string $player
     * @param int $coder
     */
    public function __construct(string $player, int $coder)
    {
        $this->player = $player;
        $this->coder = $coder;
    }

    /**
     * @param string $player
     *
     * @return bool
     */
    public function hasPlayer(string $player)
    {
        return $this->player === $player;
    }

    /**
     * @param int $coder
     *
     * @return bool
     */
    public function hasCoder(int $coder)
    {
        return $this->coder === $coder;
    }
}
