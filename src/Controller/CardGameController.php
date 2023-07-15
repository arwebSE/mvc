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

class CardGameController extends AbstractController
{
    #[Route("/card", name: "card_start")]
    public function home(): Response
    {
        return $this->render("card/home.html.twig");
    }

    #[Route("/card/deck", name: "card_deck", methods: ["GET"])]
    public function deck(SessionInterface $session): Response
    {
        $deck = new DeckOfCards();
        $session->set("card_deck", $deck); // RESET DECK SESSION

        $deck->sortDeck();
        $sortedDeck = $deck->getDeck();

        $data = [
            "sortedDeck" => $sortedDeck,
        ];
        return $this->render("card/deck.html.twig", $data);
    }

    #[Route("/card/deck/shuffle", name: "card_deck_shuffle")]
    public function shuffleDeck(SessionInterface $session): Response
    {
        $deck = new DeckOfCards();
        $session->set("card_deck", $deck); // RESET DECK SESSION
        $deck->shuffle();

        $data = [
            "players" => 3,
            "cards" => 2,
            "sortedDeck" => $deck->getDeck(),
        ];

        return $this->render("card/deck_shuffle.html.twig", $data);
    }

    #[Route("/card/deck/draw", name: "card_deck_draw")]
    public function drawCard(SessionInterface $session): Response
    {
        /** @var DeckOfCards $deck */
        $deck = $session->get("card_deck");
        $drawnCard = $deck->drawCard();
        $cardsLeft = $deck->countCards();

        $session->set("card_deck", $deck); // UPDATE DECK SESSION

        // Check if the card was successfully drawn
        if ($drawnCard === null) {
            $this->addFlash("warning", "No more cards left in the deck!");
        }

        $data = [
            "drawnCard" => $drawnCard,
            "cardsLeft" => $cardsLeft,
        ];

        return $this->render("card/deck_draw.html.twig", $data);
    }

    #[
        Route(
            "/card/deck/draw/{number<\d+>}",
            name: "card_deck_draw_multiple",
            methods: ["GET"]
        )
    ]
    public function drawMultipleCards(
        int $number,
        SessionInterface $session
    ): Response {
        /** @var DeckOfCards $deck */
        $deck = $session->get("card_deck");
        $cards = [];

        for ($i = 0; $i < $number; $i++) {
            $card = $deck->drawCard();
            if ($card === null) {
                $this->addFlash("warning", "No more cards left in the deck!");
                break;
            }
            $cards[] = $card;
        }

        $cardsLeft = $deck->countCards();
        $session->set("card_deck", $deck);

        $data = [
            "drawnCards" => $cards,
            "cardsLeft" => $cardsLeft,
        ];

        return $this->render("card/deck_draw_multiple.html.twig", $data);
    }

    #[Route("/card/deck/deal", name: "card_deck_deal_post", methods: ["POST"])]
    public function dealCardsPost(Request $request): Response
    {
        // Get the number of players and cards per player from the form
        $players = (int) $request->request->get("players", 3);
        $cards = (int) $request->request->get("cards", 2);

        // redirect to card_deck_deal route with the number of players and cards per player as parameters
        return $this->redirectToRoute("card_deck_deal", [
            "players" => $players,
            "cards" => $cards,
        ]);
    }

    #[
        Route(
            "/card/deck/deal/{players}/{cards}",
            name: "card_deck_deal",
            methods: ["GET"]
        )
    ]
    public function dealCards(
        int $players,
        int $cards,
        SessionInterface $session
    ): Response {
        /** @var DeckOfCards $deck */
        $deck = $session->get("card_deck");

        $hands = [];
        $handData = [];

        // Check if there are enough cards in the deck for the deal
        $cardsLeft = $deck->countCards();
        if ($cardsLeft < $players * $cards) {
            $this->addFlash(
                "warning",
                "Not enough cards left in the deck for the deal!"
            );
        } else {
            // Deal cards to each player and create CardHand objects to hold the cards

            for ($i = 0; $i < $players; $i++) {
                $hand = new CardHand();
                for ($j = 0; $j < $cards; $j++) {
                    /** @var Card $card */
                    $card = $deck->drawCard();
                    $hand->addCard($card);
                }
                $hands[] = $hand;
            }

            // Save the updated deck and hands to the session
            $session->set("card_deck", $deck);
            $session->set("card_hands", $hands);

            // Prepare data to display the cards in each player's hand

            foreach ($hands as $hand) {
                $handData[] = [
                    "cards" => $hand->getCards(),
                ];
            }
        }

        $cardsLeft = $deck->countCards();

        // Render the template to display the cards in each player's hand and the cards left in the deck
        $data = [
            "hands" => $handData,
            "cardsLeft" => $cardsLeft,
            "players" => $players,
            "cards" => $cards,
        ];

        return $this->render("card/deck_deal.html.twig", $data);
    }
}
