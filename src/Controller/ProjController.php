<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Card\DeckOfCards;
use App\Card\Card;
use App\Card\CardHand;
use Exception;

class ProjController extends AbstractController
{
    #[Route("/proj/about", name: "proj_about")]
    public function projAbout(): Response
    {
        return $this->render("/proj/about.html.twig");
    }

    #[Route("/proj/reset", name: "proj_reset")]
    public function projReset(SessionInterface $session): Response
    {
        // Clear the session
        $session->clear();
        $session->invalidate();
        return $this->render("/proj/reset.html.twig");
    }

    #[Route("/proj", name: "proj")]
    public function proj(SessionInterface $session, Request $request): Response
    {
        // Initialize players money
        $playerMoney = $session->get("bj_money");
        if ($playerMoney === null) {
            $playerMoney = 100;
            $session->set("bj_money", $playerMoney);
        } elseif ($playerMoney <= 0) {
            return $this->redirectToRoute("proj_reset");
        }

        $numberOfHands = $request->request->get("numHands", 1);
        $numberOfHands = max(1, min(3, (int) $numberOfHands));

        $playerHands = [];
        for ($i = 0; $i < $numberOfHands; $i++) {
            $hand = new CardHand();
            $playerHands[] = $hand;
        }

        // Initialize a new deck and shuffle it
        $deck = new DeckOfCards();
        $deck->shuffle();

        // Initialize the dealer's hand and deal two cards
        $dealerHand = new CardHand();
        $this->drawAndAddCard($deck, $dealerHand);
        $this->drawAndAddCard($deck, $dealerHand);

        // Deal two cards to each player hand
        foreach ($playerHands as $hand) {
            $this->drawAndAddCard($deck, $hand);
            $this->drawAndAddCard($deck, $hand);
        }

        // Save the new deck and hands to the session
        $session->set("bj_deck", $deck);
        $session->set("bj_player_hands", $playerHands);
        $session->set("bj_dealer_hand", $dealerHand);

        // Prepare the data for each player hand
        $playerHandsData = [];
        foreach ($playerHands as $index => $hand) {
            $playerHandsData[] = [
                "handIndex" => $index + 1,
                "cards" => $hand->getCards(),
                "handValue" => $this->calculateHandValue($hand),
            ];
        }

        // Prepare the overall data for the view
        $data = [
            "playerHands" => $playerHandsData,
            "dealerHand" => $dealerHand->getCards(),
            "playerMoney" => $playerMoney,
            "betAmount" => $session->get("bj_bet", 10),
        ];

        return $this->render("proj/index.html.twig", $data);
    }

    #[Route("/proj/deal", name: "proj_deal")]
    public function play(Request $request, SessionInterface $session): Response
    {
        // Get player's money
        $playerMoney = $session->get("bj_money");

        // Redirect to reset if the player has no money left
        if ($playerMoney <= 0) {
            return $this->redirectToRoute("proj_reset");
        }

        // Retrieve the current bet amount from the session or default to 10
        $betAmount = $session->get("bj_bet", 10);

        // Retrieve player and dealer hands from the session
        $playerHands = $session->get("bj_player_hands");
        $dealerHand = $session->get("bj_dealer_hand");

        // Check if player hands are available in the session
        if (!is_array($playerHands) || count($playerHands) == 0) {
            throw new Exception("Player hands not found in session.");
        }

        // Prepare data for each player hand
        $playerHandsData = [];
        foreach ($playerHands as $index => $hand) {
            $playerHandsData[] = [
                "handIndex" => $index + 1,
                "cards" => $hand->getCards(),
                "handValue" => $this->calculateHandValue($hand),
            ];
        }

        // Prepare the overall data for rendering the view
        $data = [
            "playerHands" => $playerHandsData,
            "dealerHand" => $dealerHand->getCards(),
            "playerMoney" => $playerMoney,
            "betAmount" => $betAmount,
        ];

        return $this->render("proj/deal.html.twig", $data);
    }

    #[Route("/proj/hit", name: "proj_hit")]
    public function hit(SessionInterface $session): Response
    {
        // Draw a new card from the deck and add it to the player's hand
        // get session data
        $deck = $session->get("bj_deck");
        if (!$deck instanceof DeckOfCards) {
            throw new Exception("Card deck not found in session.");
        }
        $playerHand = $session->get("bj_player_hand");
        if (!$playerHand instanceof CardHand) {
            throw new Exception("Player hand not found in session.");
        }
        $dealerHand = $session->get("bj_dealer_hand");
        if (!$dealerHand instanceof CardHand) {
            throw new Exception("Dealer hand not found in session.");
        }

        // Draw a new card from the deck and add it to the player's hand
        $drawnCard = $deck->drawCard();
        if ($drawnCard !== null) {
            $playerHand->addCard($drawnCard);
        }

        // Save the updated deck and player's hand to the session
        $session->set("bj_deck", $deck);
        $session->set("bj_player_hand", $playerHand);

        // Get the player's money from the session
        $playerMoney = $session->get("bj_money");
        $betAmount = $session->get("bj_bet");

        // Calculate the hand value
        $handValue = $this->calculateHandValue($playerHand);

        // If player busts
        if ($handValue > 21) {
            // Player busts, render the result template with a message
            $playerMoney = $session->get("bj_money");
            $playerMoney -= $betAmount;
            $session->set("bj_money", $playerMoney);
            $data = [
                "playerHand" => $playerHand->getCards(),
                "dealerHand" => $dealerHand->getCards(),
                "result" => "Bust! You lose.",
                "playerMoney" => $playerMoney,
            ];
            if ($playerMoney <= 0) {
                return $this->redirectToRoute("proj_reset");
            }
            return $this->render("proj/result.html.twig", $data);
        }

        // Else, the game goes on
        $data = [
            "playerHand" => $playerHand->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "handValue" => $handValue,
            "playerMoney" => $playerMoney,
        ];
        return $this->render("proj/deal.html.twig", $data);
    }

    #[Route("/proj/stand", name: "proj_stand")]
    public function stand(SessionInterface $session): Response
    {
        // get session data
        $deck = $session->get("bj_deck");
        if (!$deck instanceof DeckOfCards) {
            throw new Exception("Card deck not found in session.");
        }
        $dealerHand = $session->get("bj_dealer_hand");
        if (!$dealerHand instanceof CardHand) {
            throw new Exception("Dealer hand not found in session.");
        }

        // Get the player's money from the session
        $playerMoney = $session->get("bj_money");
        $betAmount = $session->get("bj_bet");

        // Dealer draws cards until the hand value is at least 17
        while ($this->calculateHandValue($dealerHand) < 17) {
            $drawnCard = $deck->drawCard();
            if ($drawnCard !== null) {
                $dealerHand->addCard($drawnCard);
            }
        }

        // Save the updated deck and dealer's hand to the session
        $session->set("bj_deck", $deck);
        $session->set("bj_dealer_hand", $dealerHand);
        // get session data
        $deck = $session->get("bj_deck");
        if (!$deck instanceof DeckOfCards) {
            throw new Exception("Card deck not found in session.");
        }
        $playerHand = $session->get("bj_player_hand");
        if (!$playerHand instanceof CardHand) {
            throw new Exception("Player hand not found in session.");
        }
        $dealerHand = $session->get("bj_dealer_hand");
        if (!$dealerHand instanceof CardHand) {
            throw new Exception("Dealer hand not found in session.");
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
        $session->set("bj_money", $playerMoney);

        // Render the result template with the final outcome
        $data = [
            "playerHand" => $playerHand->getCards(),
            "dealerHand" => $dealerHand->getCards(),
            "result" => $result,
            "playerMoney" => $playerMoney,
        ];
        return $this->render("proj/result.html.twig", $data);
    }

    #[Route("/proj/action", name: "proj_action")]
    public function processAction(
        Request $request,
        SessionInterface $session
    ): Response {
        $playerHands = $session->get("bj_player_hands");
        $deck = $session->get("bj_deck");
        $allHandsCompleted = true;

        foreach ($playerHands as $index => $hand) {
            $action = $request->request->get("action" . ($index + 1));

            if ($action === "hit") {
                $this->drawAndAddCard($deck, $hand);
                if ($this->calculateHandValue($hand) > 21) {
                    $hand->setStatus("bust"); // bust the hand
                } else {
                    $allHandsCompleted = false;
                }
            } elseif ($action === "stand") {
                $hand->setStatus("stand");
            } else {
                $allHandsCompleted = false;
            }
        }

        $session->set("bj_deck", $deck);
        $session->set("bj_player_hands", $playerHands);

        if ($allHandsCompleted) {
            // all hands are completed, proceed to the results page
            return $this->redirectToRoute("proj_result");
        }

        return $this->redirectToRoute("proj_deal");
    }

    #[Route("/proj/results", name: "proj_result")]
    public function showResults(SessionInterface $session): Response
    {
        $playerHands = $session->get("bj_player_hands");
        $dealerHand = $session->get("bj_dealer_hand");
        $playerMoney = $session->get("bj_money");
        $betAmount = $session->get("bj_bet", 10);

        $dealerHandValue = $this->calculateHandValue($dealerHand);
        $isDealerBust = $dealerHandValue > 21;

        $results = [];
        foreach ($playerHands as $index => $hand) {
            $handValue = $this->calculateHandValue($hand);
            $status = $hand->getStatus();

            if ($status === "bust") {
                $result = "Bust";
                $playerMoney -= $betAmount;
            } elseif ($isDealerBust || $handValue > $dealerHandValue) {
                $result = "Win";
                $playerMoney += $betAmount;
            } elseif ($handValue === $dealerHandValue) {
                $result = "Tie";
            } else {
                $result = "Lose";
                $playerMoney -= $betAmount;
            }

            $results[] = [
                "handIndex" => $index + 1,
                "cards" => $hand->getCards(),
                "handValue" => $handValue,
                "result" => $result,
            ];
        }

        $session->set("bj_money", $playerMoney);

        $data = [
            "playerHandsResults" => $results,
            "dealerHand" => $dealerHand->getCards(),
            "dealerHandValue" => $dealerHandValue,
            "isDealerBust" => $isDealerBust,
            "playerMoney" => $playerMoney,
        ];

        return $this->render("proj/result.html.twig", $data);
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
        if ($hand->getStatus() !== "active") {
            // Do not draw a card if hand inactive
            return;
        }

        $card = $deck->drawCard();
        if ($card !== null) {
            $hand->addCard($card);
        }
    }
}
