<?php

namespace App\Card;

use PHPUnit\Framework\TestCase;

class CardHandTest extends TestCase
{
    public function testConstruct(): void
    {
        $hand = new CardHand();
        $this->assertEmpty($hand->getCards());
        $this->assertInstanceOf("\App\Card\CardHand", $hand);
    }

    public function testAddCard(): void
    {
        $hand = new CardHand();
        $card = new Card("S", "1");
        $hand->addCard($card);
        $this->assertCount(1, $hand->getCards());
    }

    public function testAddCards(): void
    {
        $hand = new CardHand();
        $cards = [new Card("S", "2"), new Card("H", "3")];
        $hand->addCards($cards);
        $this->assertCount(2, $hand->getCards());
    }

    public function testRemoveCard(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card("D", "4"));
        $removedCard = $hand->removeCard(0);
        $this->assertInstanceOf(Card::class, $removedCard);
        $this->assertEmpty($hand->getCards());
    }

    public function testRemoveCardInvalidIndex(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card("D", "5"));
        $removedCard = $hand->removeCard(1);
        $this->assertNull($removedCard);
        $this->assertCount(1, $hand->getCards());
    }

    public function testRemoveLastCard(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card("C", "6"));
        $removedCard = $hand->removeLastCard();
        $this->assertInstanceOf(Card::class, $removedCard);
        $this->assertEmpty($hand->getCards());
    }

    public function testRemoveLastCardEmpty(): void
    {
        $hand = new CardHand();
        $removedCard = $hand->removeLastCard();
        $this->assertNull($removedCard);
    }

    public function testCountCards(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card("C", "7"));
        $this->assertEquals(1, $hand->countCards());
    }

    public function testGetCards(): void
    {
        $hand = new CardHand();
        $card = new Card("H", "8");
        $hand->addCard($card);
        $this->assertEquals([$card], $hand->getCards());
    }

    public function testGetCardStrings(): void
    {
        $hand = new CardHand();
        $hand->addCard(new Card("S", "9"));
        $this->assertEquals(["9S"], $hand->getCardStrings());
    }
}
