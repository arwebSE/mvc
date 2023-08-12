<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Card\DeckOfCards;
use App\Card\Card;
use App\Card\CardHand;

class Game21Controller extends AbstractController
{
    #[Route("/game", name: "game21_home")]
    public function home(): Response
    {
        return $this->render("game21/home.html.twig");
    }

    #[Route("/game21/play", name: "game21_play")]
    public function play(SessionInterface $session): Response
    {
        // Initialize the deck and shuffle
        $deck = new DeckOfCards();
        $deck->shuffle();

        // Deal two cards to the player and two cards to the dealer
        $playerHand = new CardHand();
        $dealerHand = new CardHand();
        $playerHand->addCard($deck->drawCard());
        $playerHand->addCard($deck->drawCard());
        $dealerHand->addCard($deck->drawCard());
        $dealerHand->addCard($deck->drawCard());

        // Save the deck and hands to the session
        $session->set("game21_deck", $deck);
        $session->set("game21_player_hand", $playerHand);
        $session->set("game21_dealer_hand", $dealerHand);

        // Initialize players money
        $playerMoney = $session->get("game21_money", 100); // Default to $100 if not set
        $session->set("game21_money", $playerMoney);

        // Render the game21 play template with player's and dealer's hands
        $data = [
            "playerHand" => $playerHand->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "handValue" => 0,
        ];

        return $this->render("game21/play.html.twig", $data);
    }

    #[Route("/game21/hit", name: "game21_hit")]
    public function hit(SessionInterface $session): Response
    {
        // Draw a new card from the deck and add it to the player's hand
        $deck = $session->get("game21_deck");
        $playerHand = $session->get("game21_player_hand");
        $dealerHand = $session->get("game21_dealer_hand");

        // Draw a new card from the deck and add it to the player's hand
        $drawnCard = $deck->drawCard();
        $playerHand->addCard($drawnCard);

        // Save the updated deck and player's hand to the session
        $session->set("game21_deck", $deck);
        $session->set("game21_player_hand", $playerHand);

        // Check if the player busts (hand value exceeds 21)
        $handValue = $this->calculateHandValue($playerHand);
        if ($handValue > 21) {
            // Player busts, render the result template with a message
            $data = [
                "playerHand" => $playerHand->getCards(),
                "dealerHand" => $dealerHand->getCards(),
                "result" => "Bust! You lose.",
            ];
            return $this->render("game21/result.html.twig", $data);
        }

        // Player hasn't bust, render the game21 play template with player's and dealer's hands
        $data = [
            "playerHand" => $playerHand->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "handValue" => $handValue,
        ];
        return $this->render("game21/play.html.twig", $data);
    }

    #[Route("/game21/stand", name: "game21_stand")]
    public function stand(SessionInterface $session): Response
    {
        $deck = $session->get("game21_deck");
        $dealerHand = $session->get("game21_dealer_hand");

        // Dealer draws cards until the hand value is at least 17
        while ($this->calculateHandValue($dealerHand) < 17) {
            $newCard = $deck->drawCard();
            $dealerHand->addCard($newCard);
        }

        // Save the updated deck and dealer's hand to the session
        $session->set("game21_deck", $deck);
        $session->set("game21_dealer_hand", $dealerHand);

        // Calculate the final result
        $playerHandValue = $this->calculateHandValue(
            $session->get("game21_player_hand")
        );
        $dealerHandValue = $this->calculateHandValue($dealerHand);

        // Determine the winner or if it's a tie
        $result = "";
        if (
            $dealerHandValue > 21 ||
            ($playerHandValue <= 21 && $playerHandValue > $dealerHandValue)
        ) {
            $result = "You win! Dealer has $dealerHandValue but you have $playerHandValue!";
        } elseif ($playerHandValue == $dealerHandValue) {
            $result = "It's a tie! Both you and the dealer have $playerHandValue.";
        } else {
            $result = "You lose. Dealer has $dealerHandValue and you have $playerHandValue...";
        }

        // Render the result template with the final outcome
        $data = [
            "playerHand" => $session->get("game21_player_hand")->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "result" => $result,
        ];
        return $this->render("game21/result.html.twig", $data);
    }

    /**
     * Calculates the value of a card hand considering Aces can be 1 or 11 points.
     *
     * @param CardHand $hand
     * @return int
     */
    private function calculateHandValue(CardHand $hand): int
    {
        $cards = $hand->getCards();
        $handValue = 0;
        $numAces = 0;

        // Calculate the total value of the hand
        foreach ($cards as $card) {
            $rank = $card->getRank();
            if ($rank === "A") {
                $numAces++;
                $handValue += 11;
            } elseif (in_array($rank, ["K", "Q", "J"])) {
                $handValue += 10;
            } else {
                $handValue += (int) $rank;
            }
        }

        // Adjust the value if there are aces and the hand value exceeds 21
        while ($handValue > 21 && $numAces > 0) {
            $handValue -= 10;
            $numAces--;
        }

        return $handValue;
    }
}
