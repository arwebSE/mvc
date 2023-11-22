<?php

namespace App\Card;

use PHPUnit\Framework\TestCase;

class DeckOfCardsTest extends TestCase
{
    public function testConstruct()
    {
        $deck = new DeckOfCards();
        $this->assertCount(52, $deck->getDeck());
        $this->assertInstanceOf("\App\Card\DeckOfCards", $deck);
    }

    public function testShuffle()
    {
        $deck = new DeckOfCards();
        $deckBeforeShuffle = $deck->getDeck();
        $deck->shuffle();
        $this->assertCount(52, $deck->getDeck());
        $this->assertNotEquals($deckBeforeShuffle, $deck->getDeck());
    }

    public function testCountCards()
    {
        $deck = new DeckOfCards();
        $this->assertEquals(52, $deck->countCards());
    }

    public function testDrawCard()
    {
        $deck = new DeckOfCards();
        $deck->drawCard();
        $this->assertEquals(51, $deck->countCards());
    }

    public function testDrawCardEmptyDeck()
    {
        $deck = new DeckOfCards();
        for ($i = 0; $i < 52; $i++) {
            $deck->drawCard();
        }
        $this->assertNull($deck->drawCard());
    }

    public function testSortDeck()
    {
        $deck = new DeckOfCards();
        $deck->shuffle();
        $deck->sortDeck();
        $sortedCards = $deck->getDeck();

        // first card
        $this->assertEquals("C", $sortedCards[0]->getSuit());
        $this->assertEquals("2", $sortedCards[0]->getRank());

        // last card
        $lastIndex = count($sortedCards) - 1;
        $this->assertEquals("S", $sortedCards[$lastIndex]->getSuit());
        $this->assertEquals("A", $sortedCards[$lastIndex]->getRank());
    }

    public function testGetDeck()
    {
        $deck = new DeckOfCards();
        $this->assertIsArray($deck->getDeck());
        $this->assertCount(52, $deck->getDeck());
    }
}
