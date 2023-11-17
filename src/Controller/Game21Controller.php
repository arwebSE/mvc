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
        session_start(); // if not already started
        $_SESSION = [];
        session_destroy();

        return $this->render("game21/home.html.twig");
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
        $playerHand->addCard($deck->drawCard());
        $playerHand->addCard($deck->drawCard());
        $dealerHand->addCard($deck->drawCard());
        $dealerHand->addCard($deck->drawCard());

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
        $dealerHand = $session->get("game21_dealer_hand");

        // Handle split hands:
        $playerHands = $session->get("game21_player_hands", []);
        $currentIndex = $session->get("game21_current_hand_index", 0);
        $hasSplit = count($playerHands) === 2;

        if (!empty($playerHands)) {
            $playerHand = $playerHands[$currentIndex];
        }

        // debug print both hands with pre
        echo "<pre>";
        echo "Player hand: ";
        PHP_EOL;
        print_r($playerHand);
        echo "DEALER hand: ";
        PHP_EOL;
        print_r($dealerHand);
        PHP_EOL;
        echo "has split: ";
        print_r($hasSplit);
        PHP_EOL;
        echo "player hands: ";
        print_r($playerHands);
        PHP_EOL;

        echo "</pre>";

        // Render the game21 play template with player's and dealer's hands
        $data = [
            "playerHand" => $playerHand->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "handValue" => $this->calculateHandValue($playerHand),
            "playerMoney" => $playerMoney,
            "betAmount" => $betAmount,
            "canSplit" => $this->canSplit($playerHand),
            "hasSplit" => $hasSplit,
            "playerHand1" => $playerHands[0] ?? null,
            "playerHand2" => $playerHands[1] ?? null,
            "handValue1" => isset($playerHands[0])
                ? $this->calculateHandValue($playerHands[0])
                : null,
            "handValue2" => isset($playerHands[1])
                ? $this->calculateHandValue($playerHands[1])
                : null,
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

        // Get the player's money from the session
        $playerMoney = $session->get("game21_money");
        $betAmount = $session->get("game21_bet");

        // Calculate the hand value
        $handValue = $this->calculateHandValue($playerHand->getCards());

        // Handle split hands:
        $playerHands = $session->get("game21_player_hands", []);
        $currentIndex = $session->get("game21_current_hand_index", 0);

        // Check if there are split hands
        if (!empty($playerHands)) {
            $playerHand = $playerHands[$currentIndex];
            $playerHand->addCard($drawnCard);
            $playerHands[$currentIndex] = $playerHand; // Update the modified hand back to the array
            $session->set("game21_player_hands", $playerHands);
        } else {
            $playerHand->addCard($drawnCard);
            $session->set("game21_player_hand", $playerHand);
        }
        $hasSplit = count($playerHands) === 2;

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

        // If this is a split hand and the first hand, switch to the second hand:
        if (!empty($playerHands) && $currentIndex == 0) {
            $session->set("game21_current_hand_index", 1);
            return $this->redirectToRoute("game21_play");
        }

        // Else, the game goes on
        $data = [
            "playerHand" => $playerHand->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "handValue" => $handValue,
            "playerMoney" => $playerMoney,
            "canSplit" => $this->canSplit($playerHand),
            "hasSplit" => $hasSplit,
        ];
        return $this->render("game21/play.html.twig", $data);
    }

    #[Route("/game21/stand", name: "game21_stand")]
    public function stand(SessionInterface $session): Response
    {
        $deck = $session->get("game21_deck");
        $dealerHand = $session->get("game21_dealer_hand");

        // Get the player's money from the session
        $playerMoney = $session->get("game21_money");
        $betAmount = $session->get("game21_bet");

        // Dealer draws cards until the hand value is at least 17
        while ($this->calculateHandValue($dealerHand) < 17) {
            $newCard = $deck->drawCard();
            $dealerHand->addCard($newCard);
        }

        $playerHands = $session->get("game21_player_hands", []);
        $currentIndex = $session->get("game21_current_hand_index", 0);

        // If there are split hands, determine the outcome for the active hand
        if (!empty($playerHands)) {
            $playerHand = $playerHands[$currentIndex];
            $playerHandValue = $this->calculateHandValue($playerHand);
        } else {
            $playerHandValue = $this->calculateHandValue(
                $session->get("game21_player_hand")
            );
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
            "playerHand" => $session->get("game21_player_hand")->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "result" => $result,
            "playerMoney" => $playerMoney,
        ];
        return $this->render("game21/result.html.twig", $data);
    }

    #[Route("/game21/split", name: "game21_split")]
    public function split(SessionInterface $session): Response
    {
        $deck = $session->get("game21_deck");
        $playerHand = $session->get("game21_player_hand");

        $playerMoney = $session->get("game21_money");
        $betAmount = $session->get("game21_bet");

        if ($playerMoney < $betAmount * 2) {
            // Not enough money to split, redirect or show an error message.
            return $this->redirectToRoute("game21_play");
        }

        // Split the player hand.
        $splitCard = $playerHand->removeLastCard();
        $playerSplitHand = new CardHand();
        $playerSplitHand->addCard($splitCard);

        // Draw a card for each hand.
        $playerHand->addCard($deck->drawCard());
        $playerSplitHand->addCard($deck->drawCard());

        // Deduct the additional bet amount for the split hand.
        $playerMoney -= $betAmount;

        $session->set("game21_deck", $deck);
        $session->set("game21_player_hands", [$playerHand, $playerSplitHand]);
        $session->set("game21_current_hand_index", 0); // Start with the first hand.
        $session->set("game21_money", $playerMoney);

        return $this->redirectToRoute("game21_play");
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

    private function canSplit(CardHand $hand)
    {
        if (
            count($hand->getCards()) == 2 &&
            $hand->getCards()[0]->getRank() == $hand->getCards()[1]->getRank()
        ) {
            return true;
        }
        return false;
    }
}
