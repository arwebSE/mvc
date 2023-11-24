<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Card\DeckOfCards;
use App\Card\CardHand;
use App\Card\Card;

class CardGameApiController extends AbstractController
{
    #[Route("/api/deck", name: "api_deck", methods: ["GET"])]
    public function getDeck(): JsonResponse
    {
        $deck = new DeckOfCards();
        $deck->sortDeck();

        $sortedDeck = $deck->getDeck();
        $data = [];

        foreach ($sortedDeck as $card) {
            $data[] = [
                "rank" => $card->getRank(),
                "suit" => $card->getSuit(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route("/api/deck/shuffle", name: "api_deck_shuffle", methods: ["POST"])]
    public function postShuffleDeck(SessionInterface $session): JsonResponse
    {
        $deck = new DeckOfCards();
        $deck->shuffle();
        $session->set("card_deck", $deck); // SAVES SHUFFLED DECK TO SESSION

        $shuffledDeck = $deck->getDeck();
        $data = [];

        foreach ($shuffledDeck as $card) {
            $data[] = [
                "rank" => $card->getRank(),
                "suit" => $card->getSuit(),
            ];
        }

        return new JsonResponse($data);
    }

    /**
     * Draw cards from the deck.
     *
     * @param int $number The number of cards to draw.
     * @param SessionInterface $session The session interface.
     *
     * @return array Returns an associative array containing:
     *               - "message" (string, optional): A message if no more cards are left.
     *               - "cards" (array): An array of arrays, each representing a card with "rank" and "suit".
     *               - "cardsLeft" (int): The number of cards left in the deck.
     */
    private function drawCardsFromDeck(
        int $number,
        SessionInterface $session
    ): array {
        /** @var DeckOfCards $deck */
        $deck = $session->get("card_deck");
        $cards = [];

        for ($i = 0; $i < $number; $i++) {
            $card = $deck->drawCard();
            if ($card === null) {
                $cardsLeft = $deck->countCards();
                $data = [
                    "message" => "No more cards left in the deck!",
                    "cards" => $cards,
                    "cardsLeft" => $cardsLeft,
                ];
                return $data;
            }

            $cards[] = [
                "rank" => $card->getRank(),
                "suit" => $card->getSuit(),
            ];
        }

        $cardsLeft = $deck->countCards();
        $session->set("card_deck", $deck);

        $data = [
            "cards" => $cards,
            "cardsLeft" => $cardsLeft,
        ];

        return $data;
    }

    #[Route("/api/deck/draw", name: "api_deck_draw", methods: ["POST"])]
    public function drawCard(SessionInterface $session): JsonResponse
    {
        /** @var array<Card> */
        $data = $this->drawCardsFromDeck(1, $session);

        if (isset($data["cards"][0])) {
            $data["cards"] = $data["cards"][0];
        }

        return new JsonResponse($data);
    }

    #[
        Route(
            "/api/deck/draw/{number}",
            name: "api_deck_draw_multiple",
            methods: ["POST"]
        )
    ]
    public function drawMultipleCards(
        int $number,
        SessionInterface $session
    ): JsonResponse {
        $data = $this->drawCardsFromDeck($number, $session);

        return new JsonResponse($data);
    }

    #[
        Route(
            "/api/deck/deal/{players}/{cards}",
            name: "api_deck_deal",
            methods: ["POST"]
        )
    ]
    public function dealCards(
        int $players,
        int $cards,
        SessionInterface $session
    ): JsonResponse {
        /** @var DeckOfCards $deck */
        $deck = $session->get("card_deck");

        if ($deck->countCards() < $players * $cards) {
            $data = [
                "message" => "Not enough cards left in the deck!",
                "cardsLeft" => $deck->countCards(),
            ];
            return new JsonResponse($data);
        }

        $hands = [];
        for ($i = 1; $i <= $players; $i++) {
            $hand = new CardHand();
            for ($j = 0; $j < $cards; $j++) {
                $card = $deck->drawCard();
                if ($card !== null) {
                    $hand->addCard($card);
                }
            }
            $hands["Player $i"] = $hand->getCardStrings();
        }

        $cardsLeft = $deck->countCards();
        $session->set("card_deck", $deck);

        $data = [
            "hands" => $hands,
            "cardsLeft" => $cardsLeft,
        ];

        return new JsonResponse($data);
    }
}
