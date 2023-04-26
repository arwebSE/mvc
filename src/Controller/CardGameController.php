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
        $numCards = $request->request->get('num_cards');

        $deck = new DeckOfCards();
        $deck->shuffle();

        $hand = new CardHand();
        for ($i = 1; $i <= $numCards; $i++) {
            $hand->addCard($deck->drawCard());
        }

        $session->set("card_deck", $deck);
        $session->set("card_hand", $hand);
        $session->set("card_drawn", []); // Initialize card_drawn session variable

        return $this->redirectToRoute('card_play');
    }

    #[Route("/game/card/play", name: "card_play", methods: ['GET'])]
    public function play(
        SessionInterface $session
    ): Response {
        $hand = $session->get("card_hand");

        $data = [
            "cardHand" => $hand->getCardStrings(),
        ];

        return $this->render('card/play.html.twig', $data);
    }

    #[Route("/game/card/draw", name: "card_draw", methods: ['POST'])]
    public function draw(
        SessionInterface $session
    ): Response {
        $deck = $session->get('card_deck');
        $card = $deck->drawCard();
        $drawnCards = $session->get('card_drawn');

        if ($card !== null) {
            $drawnCards[] = $card->getAsString();
            $session->set('card_drawn', $drawnCards);
        } else {
            $this->addFlash(
                'warning',
                'No more cards left in the deck!'
            );
        }

        return $this->redirectToRoute('card_play');
    }
}
