<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Card\DeckOfCards;
use App\Card\Card;
use App\Card\CardHand;

class JsonController extends AbstractController
{
    #[Route("/api/quote", name: "api_quote")]
    public function quoteGenerator(): JsonResponse
    {
        $quotes = [
            [
                "quote" =>
                    "Life isn’t about getting and having, it’s about giving and being.",
                "author" => "Kevin Kruse",
            ],
            [
                "quote" =>
                    "Whatever the mind of man can conceive and believe, it can achieve.",
                "author" => "Napoleon Hill",
            ],
            [
                "quote" =>
                    "Strive not to be a success, but rather to be of value.",
                "author" => "Albert Einstein",
            ],
            [
                "quote" =>
                    "Two roads diverged in a wood, and I—I took the one less traveled by, And that has made all the difference.",
                "author" => "Robert Frost",
            ],
            [
                "quote" =>
                    "I attribute my success to this: I never gave or took any excuse.",
                "author" => "Florence Nightingale",
            ],
            [
                "quote" => "You miss 100% of the shots you don’t take.",
                "author" => "Wayne Gretzky",
            ],
            [
                "quote" =>
                    "I’ve missed more than 9000 shots in my career. I’ve lost almost 300 games. 26 times I’ve been trusted to take the game winning shot and missed. I’ve failed over and over and over again in my life. And that is why I succeed.",
                "author" => "Michael Jordan",
            ],
        ];

        $rand = random_int(0, count($quotes) - 1);
        $quote = $quotes[$rand]["quote"];
        $author = $quotes[$rand]["author"];

        $datetime = date("Y-m-d H:i:s");

        $data = [
            "datetime" => $datetime,
            "quote" => $quote . " - " . $author,
        ];

        $response = new JsonResponse($data);
        $response->setEncodingOptions(
            $response->getEncodingOptions() |
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE
        );

        return $response;
    }

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
     * @param int               $number  The number of cards to draw.
     * @param SessionInterface  $session The session interface.
     *
     * @return array{
     *     "message"?: string,
     *     "cards": array{
     *         "rank": string,
     *         "suit": string
     *     }[],
     *     "cardsLeft": int
     * }
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
