<?php

namespace App\Card;

class DeckOfCards
{
    private $cards = [];

    public function __construct()
    {
        $suits = ['H', 'D', 'C', 'S'];
        $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

        foreach ($suits as $suit) {
            foreach ($ranks as $rank) {
                $this->cards[] = new CardGraphic($suit, $rank);
            }
        }
    }

    public function shuffle(): void
    {
        shuffle($this->cards);
    }

    public function drawCard(): ?CardGraphic
    {
        return array_shift($this->cards);
    }

    public function countCards(): int
    {
        return count($this->cards);
    }
}
