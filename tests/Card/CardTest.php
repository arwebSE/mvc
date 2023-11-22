<?php

namespace App\Card;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for class Guess.
 */
class CardTest extends TestCase
{
    /**
     * Construct object.
     */
    public function testConstruct()
    {
        $card = new Card("S", "1");
        $this->assertInstanceOf("\App\Card\Card", $card);
    }

    public function testGetSuit()
    {
        $card = new Card("H", "2");
        $this->assertEquals("H", $card->getSuit());
    }

    public function testGetRank()
    {
        $card = new Card("S", "3");
        $this->assertEquals("3", $card->getRank());
    }

    public function testGetAsString()
    {
        $card = new Card("D", "4");
        $this->assertEquals("4D", $card->getAsString());
    }

    public function testGetSuitSymbol()
    {
        $suits = ["H" => "♥", "D" => "♦", "C" => "♣", "S" => "♠", "X" => "?"];
        foreach ($suits as $suit => $symbol) {
            $card = new Card($suit, "5");
            $this->assertEquals($symbol, $card->getSuitSymbol());
        }
    }
}