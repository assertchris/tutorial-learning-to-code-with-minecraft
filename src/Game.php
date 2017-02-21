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
    public function hasPlayer($player = null)
    {
        if (is_null($player)) {
            return !is_null($this->player);
        }

        return $this->player === $player;
    }

    /**
     * @return string
     */
    public function getPlayer() {
        return $this->player;
    }

    /**
     * @param null|int $coder
     *
     * @return bool
     */
    public function hasCoder($coder = null)
    {
        if (is_null($coder)) {
            return !is_null($this->coder);
        }

        return $this->coder === $coder;
    }

    /**
     * @return string
     */
    public function getCoder() {
        return $this->coder;
    }
}
