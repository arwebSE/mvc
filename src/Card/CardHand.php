<?php

namespace App\Card;

class CardHand
{
    private array $cards = [];

    public function __construct()
    {
        $this->cards = [];
    }

    public function addCard(Card $card): void
    {
        $this->cards[] = $card;
    }

    public function addCards(array $cards): void
    {
        foreach ($cards as $card) {
            $this->addCard($card);
        }
    }

    public function removeCard(int $index): ?Card
    {
        if (isset($this->cards[$index])) {
            $removedCard = $this->cards[$index];
            array_splice($this->cards, $index, 1);
            return $removedCard;
        }
        return null;
    }

    public function countCards(): int
    {
        return count($this->cards);
    }

    public function getCards(): array
    {
        return $this->cards;
    }

    public function getCardStrings(): array
    {
        $cardStrings = [];
        foreach ($this->cards as $card) {
            $cardStrings[] = $card->getAsString();
        }
        return $cardStrings;
    }
}
