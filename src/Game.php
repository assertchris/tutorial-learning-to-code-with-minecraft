<?php

use Theory\Builder\Client;

class Game
{
    const POSITION_NONE = 0;
    const POSITION_NORTH_WEST = 1;
    const POSITION_NORTH = 2;
    const POSITION_NORTH_EAST = 3;
    const POSITION_WEST = 4;
    const POSITION_ORIGIN = 5;
    const POSITION_EAST = 6;
    const POSITION_SOUTH_WEST = 7;
    const POSITION_SOUTH = 8;
    const POSITION_SOUTH_EAST = 9;

    /**
     * @var string
     */
    public $player;

    /**
     * @var int
     */
    public $coder;

    /**
     * @var int
     */
    public $position = self::POSITION_NONE;

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
     * @param Client $x
     * @param int $x
     * @param int $y
     * @param int $z
     * @param null|callable $then
     */
    public function build(Client $builder, int $x, int $y, int $z, $then = null)
    {
        $builder->exec(
            sprintf(
                "fill %s %s %s %s %s %s stained_glass 7",
                $x, $y, $z, $x + 22, $y, $z + 22
            )
        );

        $builder->exec(
            sprintf(
                "fill %s %s %s %s %s %s stained_glass 7",
                $x, $y, $z, $x, $y + 12, $z + 22
            )
        );

        $builder->exec(
            sprintf(
                "fill %s %s %s %s %s %s stained_glass 7",
                $x + 22, $y, $z, $x + 22, $y + 12, $z + 22
            )
        );

        $builder->exec(
            sprintf(
                "fill %s %s %s %s %s %s stained_glass 7",
                $x, $y, $z, $x + 22, $y + 12, $z
            )
        );

        $builder->exec(
            sprintf(
                "fill %s %s %s %s %s %s stained_glass 7",
                $x, $y, $z + 22, $x + 22, $y + 12, $z + 22
            )
        );

        $builder->exec(
            sprintf(
                "fill %s %s %s %s %s %s stained_glass 7",
                $x, $y + 12, $z, $x + 22, $y + 12, $z + 22
            )
        );

        $builder->exec(
            sprintf(
                "fill %s %s %s %s %s %s lava",
                $x + 1, $y + 1, $z + 1, $x + 22 - 1, $y + 1, $z + 22 - 1
            )
        );

        $builder->exec(
            sprintf(
                "fill %s %s %s %s %s %s stained_glass 7",
                $x + 3, $y + 3, $z + 3, $x + 22 - 3, $y + 3, $z + 22 - 3
            )
        );

        if (is_callable($then)) {
            $then();
        }
    }

    /**
     * @param Client $x
     * @param int $x
     * @param int $y
     * @param int $z
     * @param null|callable $then
     */
    public function clear(Client $builder, int $x, int $y, int $z, $then = null)
    {
        for ($i = 0; $i < 20; $i++) {
            $builder->exec(
                sprintf(
                    "fill %s %s %s %s %s %s air",
                    $x, $y + $i, $z, $x + 22, $y + $i, $z + 22
                )
            );
        }

        if (is_callable($then)) {
            $then();
        }
    }
}
