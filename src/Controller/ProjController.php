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
    // About Page
    #[Route("/proj/about", name: "proj_about")]
    public function projAbout(): Response
    {
        return $this->render("/proj/about.html.twig");
    }

    // Reset Game
    #[Route("/proj/reset", name: "proj_reset")]
    public function projReset(SessionInterface $session): Response
    {
        $session->clear();
        $session->invalidate();
        return $this->redirectToRoute("proj");
    }

    // Main Game Route
    #[Route("/proj", name: "proj")]
    public function proj(SessionInterface $session, Request $request): Response
    {
        $playerMoney = $session->get("bj_money", 100);
        if ($playerMoney <= 0) {
            return $this->redirectToRoute("proj_reset");
        }

        $this->initializeGame($session, $request);
        return $this->renderGameView($session);
    }

    // Handle Game Actions
    #[Route("/proj/action", name: "proj_action")]
    public function processAction(
        Request $request,
        SessionInterface $session
    ): Response {
        $allHandsCompleted = $this->handlePlayerActions($request, $session);

        if ($allHandsCompleted) {
            // All hands are completed, proceed to the results page
            return $this->redirectToRoute("proj_results");
        }

        return $this->redirectToRoute("proj_deal");
    }

    // Show Results
    #[Route("/proj/results", name: "proj_results")]
    public function showResults(SessionInterface $session): Response
    {
        return $this->renderResultsView($session);
    }

    // Helper Functions
    private function initializeGame(
        SessionInterface $session,
        Request $request
    ): void {
        $deck = new DeckOfCards();
        $deck->shuffle();

        $numberOfHands = $request->request->get("numHands", 1);
        $numberOfHands = max(1, min(3, (int) $numberOfHands));

        $playerHands = $this->dealPlayerHands($numberOfHands, $deck);
        $dealerHand = $this->dealDealerHand($deck);

        $session->set("bj_deck", $deck);
        $session->set("bj_player_hands", $playerHands);
        $session->set("bj_dealer_hand", $dealerHand);
        $session->set("bj_bet", $request->request->get("betAmount", 10));
    }

    private function renderGameView(SessionInterface $session): Response
    {
        $playerHands = $session->get("bj_player_hands");
        $dealerHand = $session->get("bj_dealer_hand");
        $playerMoney = $session->get("bj_money", 100);
        $betAmount = $session->get("bj_bet", 10);

        // Prepare data for each player hand
        $playerHandsData = [];
        foreach ($playerHands as $index => $hand) {
            $playerHandsData[] = [
                "handIndex" => $index + 1,
                "cards" => $hand->getCards(),
                "handValue" => $this->calculateHandValue($hand),
                "status" => $hand->getStatus(),
            ];
        }

        // Prepare dealer hand data
        $dealerHandData = [
            "cards" => $dealerHand->getCards(),
            "handValue" => $this->calculateHandValue($dealerHand),
            "isDealerHandHidden" => true, // Initially, dealer's first card is hidden
        ];

        // Prepare overall data for the view
        $data = [
            "playerHands" => $playerHandsData,
            "dealerHand" => $dealerHandData,
            "playerMoney" => $playerMoney,
            "betAmount" => $betAmount,
        ];

        return $this->render("proj/deal.html.twig", $data);
    }

    private function handlePlayerActions(
        Request $request,
        SessionInterface $session
    ): bool {
        $playerHands = $session->get("bj_player_hands");
        $deck = $session->get("bj_deck");
        $allHandsCompleted = true;

        foreach ($playerHands as $index => $hand) {
            $action = $request->request->get("action" . ($index + 1));

            if ($action === "hit") {
                $this->drawAndAddCard($deck, $hand);
                if ($this->calculateHandValue($hand) > 21) {
                    $hand->setStatus("bust");
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

        return $allHandsCompleted;
    }

    private function renderResultsView(SessionInterface $session): Response
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

    private function dealPlayerHands(
        int $numberOfHands,
        DeckOfCards $deck
    ): array {
        $playerHands = [];
        for ($i = 0; $i < $numberOfHands; $i++) {
            $hand = new CardHand();
            $this->drawAndAddCard($deck, $hand); // Deal the first card
            $this->drawAndAddCard($deck, $hand); // Deal the second card
            $playerHands[] = $hand;
        }
        return $playerHands;
    }

    private function dealDealerHand(DeckOfCards $deck): CardHand
    {
        $dealerHand = new CardHand();
        $this->drawAndAddCard($deck, $dealerHand); // Deal the first card
        $this->drawAndAddCard($deck, $dealerHand); // Deal the second card
        return $dealerHand;
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
