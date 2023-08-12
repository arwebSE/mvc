<?php

namespace App\Card;

class DeckOfCards
{
    /**
     * @var Card[]
     */
    private array $cards = [];

    public function __construct()
    {
        $suits = ["H", "D", "C", "S"];
        $ranks = [
            "2",
            "3",
            "4",
            "5",
            "6",
            "7",
            "8",
            "9",
            "10",
            "J",
            "Q",
            "K",
            "A",
        ];

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
        if (empty($this->cards)) {
            return null;
        }

        $card = $this->cards[0];
        array_shift($this->cards); // remove the drawn card from the deck
        return $card;
    }

    public function sortDeck(): void
    {
        usort($this->cards, function ($card1, $card2) {
            $compareSuit = strcmp($card1->getSuit(), $card2->getSuit());
            $rankValues = [
                "2",
                "3",
                "4",
                "5",
                "6",
                "7",
                "8",
                "9",
                "10",
                "J",
                "Q",
                "K",
                "A",
            ];

            $compareRank =
                array_search($card1->getRank(), $rankValues) <=>
                array_search($card2->getRank(), $rankValues);

            return $compareSuit === 0 ? $compareRank : $compareSuit;
        });
    }

    /**
     * @return Card[]
     */
    public function getDeck(): array
    {
        return $this->cards;
    }
}
