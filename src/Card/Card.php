<?php

namespace App\Card;

/**
 * Represents a playing card with a suit and a rank.
 */
class Card
{
    /**
     * @var string The suit of the card (e.g., 'H', 'D', 'C', 'S').
     */
    protected string $suit;

    /**
     * @var string The rank of the card (e.g., '2', '3', ... 'A').
     */
    protected string $rank;

    /**
     * Constructs a new Card instance.
     *
     * @param string $suit The suit of the card.
     * @param string $rank The rank of the card.
     */
    public function __construct(string $suit, string $rank)
    {
        $this->suit = $suit;
        $this->rank = $rank;
    }

    /**
     * Gets the suit of the card.
     *
     * @return string The suit of the card.
     */
    public function getSuit(): string
    {
        return $this->suit;
    }

    /**
     * Gets the rank of the card.
     *
     * @return string The rank of the card.
     */
    public function getRank(): string
    {
        return $this->rank;
    }

    /**
     * Returns a string representation of the card.
     *
     * @return string The card as a string in the format 'rankSuit'.
     */
    public function getAsString(): string
    {
        return $this->rank . $this->suit;
    }

    /**
     * Gets the symbol of the card's suit.
     *
     * @return string The symbol of the suit.
     *                Returns '?' for invalid suits.
     */
    public function getSuitSymbol(): string
    {
        return match ($this->suit) {
            'H' => '♥',
            'D' => '♦',
            'C' => '♣',
            'S' => '♠',
            default => '?', // Handle invalid suits gracefully
        };
    }
}
