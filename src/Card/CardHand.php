<?php

namespace App\Card;

class CardHand
{
    /**
     * @var Card[]
     */
    private array $cards = [];

    private $status = "active";

    public function __construct()
    {
        $this->cards = [];
    }

    public function addCard(Card $card): void
    {
        $this->cards[] = $card;
    }

    /**
     * @param Card[] $cards
     */
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

    public function removeLastCard(): ?Card
    {
        if (!empty($this->cards)) {
            return array_pop($this->cards);
        }
        return null;
    }

    public function countCards(): int
    {
        return count($this->cards);
    }

    /**
     * @return Card[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * @return string[]
     */
    public function getCardStrings(): array
    {
        $cardStrings = [];
        foreach ($this->cards as $card) {
            $cardStrings[] = $card->getAsString();
        }
        return $cardStrings;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
