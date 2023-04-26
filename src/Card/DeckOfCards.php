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
                $this->cards[] = new Card($suit, $rank);
            }
        }
    }

    public function shuffle(): void
    {
        shuffle($this->cards);
    }

    public function countCards(): int
    {
        return count($this->cards);
    }

    public function drawCard(): ?Card
    {
        if ($this->countCards() > 0) {
            $card = $this->cards[0];
            array_shift($this->cards); // remove the drawn card from the deck
            return $card;
        }
        return null;
    }

    public function sortDeck(): void
    {
        usort($this->cards, function ($a, $b) {
            $compareSuit = strcmp($a->getSuit(), $b->getSuit());
            $rankValues = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

            $compareRank = array_search($a->getRank(), $rankValues) <=> array_search($b->getRank(), $rankValues);

            return $compareSuit === 0 ? $compareRank : $compareSuit;
        });
    }

    public function getDeck(): array
    {
        return $this->cards;
    }
}
