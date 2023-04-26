<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Card\DeckOfCards;
use App\Card\CardHand;

class CardGameController extends AbstractController
{

    #[Route("/game/card", name: "card_start")]
    public function home(): Response
    {
        return $this->render('card/home.html.twig');
    }

    #[Route("/game/card/init", name: "card_init_get", methods: ['GET'])]
    public function init(): Response
    {
        return $this->render('card/init.html.twig');
    }

    #[Route("/game/card/init", name: "card_init_post", methods: ['POST'])]
    public function initCallback(
        Request $request,
        SessionInterface $session
    ): Response {
        $deck = new DeckOfCards();
        $deck->shuffle();

        $session->set("card_deck", $deck);  // NEW SHUFFLED DECK SESSION

        return $this->redirectToRoute('card_play');
    }

    #[Route("/game/card/deck", name: "card_deck", methods: ['GET'])]
    public function deck(
        SessionInterface $session
    ): Response {
        $deck = new DeckOfCards();
        $session->set("card_deck", $deck); // RESET DECK SESSION

        $deck->sortDeck();
        $sortedDeck = $deck->getDeck();

        $data = [
            "sortedDeck" => $sortedDeck,
        ];
        return $this->render('card/deck.html.twig', $data);
    }

    #[Route("/game/card/deck/shuffle", name: "card_deck_shuffle")]
    public function shuffleDeck(SessionInterface $session): Response
    {
        $deck = $session->get("card_deck");
        $deck->shuffle();

        $data = [
            "sortedDeck" => $deck->getDeck(),
        ];

        return $this->render('card/deck_shuffle.html.twig', $data);
    }

    #[Route("/game/card/deck/draw", name: "card_deck_draw")]
    public function drawCard(SessionInterface $session): Response
    {
        $deck = $session->get("card_deck");
        $drawnCard = $deck->drawCard();
        $cardsLeft = $deck->countCards();

        $session->set("card_deck", $deck); // UPDATE DECK SESSION

        // Check if the card was successfully drawn
        if ($drawnCard === null) {
            $this->addFlash(
                'warning',
                'No more cards left in the deck!'
            );
        }

        $data = [
            "drawnCard" => $drawnCard,
            "cardsLeft" => $cardsLeft,
        ];

        return $this->render('card/deck_draw.html.twig', $data);
    }

    #[Route('/game/card/deck/draw/{number<\d+>}', name: 'card_deck_draw_multiple', methods: ['GET'])]
    public function drawMultipleCards(int $number, SessionInterface $session): Response
    {
        $deck = $session->get('card_deck');
        $cards = [];

        for ($i = 0; $i < $number; $i++) {
            $card = $deck->drawCard();
            if ($card !== null) {
                $cards[] = $card;
            } else {
                $this->addFlash(
                    'warning',
                    'No more cards left in the deck!'
                );
                break;
            }
        }

        $cardsLeft = $deck->countCards();
        $session->set('card_deck', $deck);

        $data = [
            'drawnCards' => $cards,
            'cardsLeft' => $cardsLeft,
        ];

        return $this->render('card/deck_draw_multiple.html.twig', $data);
    }
}
