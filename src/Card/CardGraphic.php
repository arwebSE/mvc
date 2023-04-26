<?php

namespace App\Card;

class CardGraphic extends Card
{
    public function __construct(string $suit, string $rank)
    {
        parent::__construct($suit, $rank);
    }

    public function getAsString(): string
    {
        return $this->rank . $this->suit;
    }
}
