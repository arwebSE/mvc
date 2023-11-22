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
    public function home(SessionInterface $session): Response
    {
        // Clear the session
        $session->clear();
        $session->invalidate();
        return $this->render("game21/home.html.twig");
    }

    #[Route("/game/doc", name: "game21_doc")]
    public function doc(): Response
    {
        return $this->render("game21/doc.html.twig");
    }

    #[Route("/game21/init", name: "game21_init")]
    public function init(SessionInterface $session): Response
    {
        // Initialize players money
        $playerMoney = $session->get("game21_money");
        if ($playerMoney === null) {
            $playerMoney = 100;
            $session->set("game21_money", $playerMoney);
        } elseif ($playerMoney <= 0) {
            return $this->render("game21/gameover.html.twig");
        }

        // Always initialize a new deck and shuffle it
        $deck = new DeckOfCards();
        $deck->shuffle();

        // Always deal two cards to the player and the dealer
        $playerHand = new CardHand();
        $dealerHand = new CardHand();
        $this->drawAndAddCard($deck, $playerHand);
        $this->drawAndAddCard($deck, $playerHand);
        $this->drawAndAddCard($deck, $dealerHand);
        $this->drawAndAddCard($deck, $dealerHand);

        // Always save the new deck and hands to the session
        $session->set("game21_deck", $deck);
        $session->set("game21_player_hand", $playerHand);
        $session->set("game21_dealer_hand", $dealerHand);

        // Render init page
        $data = [
            "playerHand" => $playerHand->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "handValue" => $this->calculateHandValue($playerHand),
            "playerMoney" => $playerMoney,
            "betAmount" => $session->get("game21_bet", 10),
        ];

        return $this->render("game21/init.html.twig", $data);
    }

    #[Route("/game21/play", name: "game21_play")]
    public function play(Request $request, SessionInterface $session): Response
    {
        // Get players money
        $playerMoney = $session->get("game21_money");

        if ($playerMoney <= 0) {
            return $this->render("game21/gameover.html.twig");
        }

        $betAmount = $request->request->get("betAmount");
        $session->set("game21_bet", $betAmount);

        // get session data
        $playerHand = $session->get("game21_player_hand");
        if (!$playerHand instanceof CardHand) {
            throw new \Exception("Player hand not found in session.");
        }
        $dealerHand = $session->get("game21_dealer_hand");
        if (!$dealerHand instanceof CardHand) {
            throw new \Exception("Dealer hand not found in session.");
        }

        // Render the game21 play template with player's and dealer's hands
        $data = [
            "playerHand" => $playerHand->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "handValue" => $this->calculateHandValue($playerHand),
            "playerMoney" => $playerMoney,
            "betAmount" => $betAmount,
        ];

        return $this->render("game21/play.html.twig", $data);
    }

    #[Route("/game21/hit", name: "game21_hit")]
    public function hit(SessionInterface $session): Response
    {
        // Draw a new card from the deck and add it to the player's hand
        // get session data
        $deck = $session->get("game21_deck");
        if (!$deck instanceof DeckOfCards) {
            throw new \Exception("Card deck not found in session.");
        }
        $playerHand = $session->get("game21_player_hand");
        if (!$playerHand instanceof CardHand) {
            throw new \Exception("Player hand not found in session.");
        }
        $dealerHand = $session->get("game21_dealer_hand");
        if (!$dealerHand instanceof CardHand) {
            throw new \Exception("Dealer hand not found in session.");
        }

        // Draw a new card from the deck and add it to the player's hand
        $drawnCard = $deck->drawCard();
        if ($drawnCard !== null) {
            $playerHand->addCard($drawnCard);
        }

        // Save the updated deck and player's hand to the session
        $session->set("game21_deck", $deck);
        $session->set("game21_player_hand", $playerHand);

        // Get the player's money from the session
        $playerMoney = $session->get("game21_money");
        $betAmount = $session->get("game21_bet");

        // Calculate the hand value
        $handValue = $this->calculateHandValue($playerHand);

        // If player busts
        if ($handValue > 21) {
            // Player busts, render the result template with a message
            $playerMoney = $session->get("game21_money");
            $playerMoney -= $betAmount;
            $session->set("game21_money", $playerMoney);
            $data = [
                "playerHand" => $playerHand->getCards(),
                "dealerHand" => $dealerHand->getCards(),
                "result" => "Bust! You lose.",
                "playerMoney" => $playerMoney,
            ];
            if ($playerMoney <= 0) {
                return $this->render("game21/gameover.html.twig");
            }
            return $this->render("game21/result.html.twig", $data);
        }

        // Else, the game goes on
        $data = [
            "playerHand" => $playerHand->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "handValue" => $handValue,
            "playerMoney" => $playerMoney,
        ];
        return $this->render("game21/play.html.twig", $data);
    }

    #[Route("/game21/stand", name: "game21_stand")]
    public function stand(SessionInterface $session): Response
    {
        // get session data
        $deck = $session->get("game21_deck");
        if (!$deck instanceof DeckOfCards) {
            throw new \Exception("Card deck not found in session.");
        }
        $dealerHand = $session->get("game21_dealer_hand");
        if (!$dealerHand instanceof CardHand) {
            throw new \Exception("Dealer hand not found in session.");
        }

        // Get the player's money from the session
        $playerMoney = $session->get("game21_money");
        $betAmount = $session->get("game21_bet");

        // Dealer draws cards until the hand value is at least 17
        while ($this->calculateHandValue($dealerHand) < 17) {
            $drawnCard = $deck->drawCard();
            if ($drawnCard !== null) {
                $dealerHand->addCard($drawnCard);
            }
        }

        // Save the updated deck and dealer's hand to the session
        $session->set("game21_deck", $deck);
        $session->set("game21_dealer_hand", $dealerHand);
        // get session data
        $deck = $session->get("game21_deck");
        if (!$deck instanceof DeckOfCards) {
            throw new \Exception("Card deck not found in session.");
        }
        $playerHand = $session->get("game21_player_hand");
        if (!$playerHand instanceof CardHand) {
            throw new \Exception("Player hand not found in session.");
        }
        $dealerHand = $session->get("game21_dealer_hand");
        if (!$dealerHand instanceof CardHand) {
            throw new \Exception("Dealer hand not found in session.");
        }

        // Calculate the final result
        $playerHandValue = $this->calculateHandValue($playerHand);
        $dealerHandValue = $this->calculateHandValue($dealerHand);

        // Determine the winner or if it's a tie
        $result = "";
        if (
            $dealerHandValue > 21 ||
            ($playerHandValue <= 21 && $playerHandValue > $dealerHandValue)
        ) {
            $playerMoney += $betAmount;
            $result = "You win! Dealer has $dealerHandValue but you have $playerHandValue!";
        } elseif ($playerHandValue == $dealerHandValue) {
            $result = "It's a tie! Both you and the dealer have $playerHandValue.";
        } else {
            $playerMoney -= $betAmount;
            $result = "You lose. Dealer has $dealerHandValue and you have $playerHandValue...";
        }

        // Save the updated player's money to the session
        $session->set("game21_money", $playerMoney);

        // Render the result template with the final outcome
        $data = [
            "playerHand" => $playerHand->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "result" => $result,
            "playerMoney" => $playerMoney,
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

    /**
     * Draws a card from the deck and adds it to the specified hand if it's not null.
     *
     * @param DeckOfCards $deck The deck of cards to draw from.
     * @param CardHand $hand The hand to which the card will be added.
     */
    private function drawAndAddCard(DeckOfCards $deck, CardHand $hand): void
    {
        $card = $deck->drawCard();
        if ($card !== null) {
            $hand->addCard($card);
        }
    }
}
