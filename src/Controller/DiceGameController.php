<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Dice\Dice;
use App\Dice\DiceGraphic;
use App\Dice\DiceHand;

class DiceGameController extends AbstractController
{
    #[Route("/game/pig", name: "pig_start")]
    public function home(): Response
    {
        return $this->render("pig/home.html.twig");
    }

    #[Route("/game/pig/test/roll", name: "test_roll_die")]
    public function testRollDie(): Response
    {
        //$die = new Dice();
        $die = new DiceGraphic();

        $data = [
            "dice" => $die->roll(),
            "diceString" => $die->getAsString(),
        ];

        return $this->render("pig/test/roll.html.twig", $data);
    }

    #[Route("/game/pig/test/roll/{num<\d+>}", name: "test_roll_num_dice")]
    public function testRollDice(int $num): Response
    {
        if ($num > 99) {
            throw new \Exception("Can not roll more than 99 dice!");
        }

        $diceRoll = [];
        for ($i = 1; $i <= $num; $i++) {
            //$die = new Dice();
            $die = new DiceGraphic();
            $die->roll();
            $diceRoll[] = $die->getAsString();
        }

        $data = [
            "num_dice" => count($diceRoll),
            "diceRoll" => $diceRoll,
        ];

        return $this->render("pig/test/roll_many.html.twig", $data);
    }

    #[Route("/game/pig/test/dicehand/{num<\d+>}", name: "test_dicehand")]
    public function testDiceHand(int $num): Response
    {
        if ($num > 99) {
            throw new \Exception("Can not roll more than 99 dice!");
        }

        $hand = new DiceHand();
        for ($i = 1; $i <= $num; $i++) {
            $die = $i % 2 === 1 ? new DiceGraphic() : new Dice();
            $hand->add($die);
        }

        $hand->roll();

        $data = [
            "num_dice" => $hand->getNumberDice(),
            "diceRoll" => $hand->getString(),
        ];

        return $this->render("pig/test/dicehand.html.twig", $data);
    }

    #[Route("/game/pig/init", name: "pig_init_get", methods: ["GET"])]
    public function init(): Response
    {
        return $this->render("pig/init.html.twig");
    }

    #[Route("/game/pig/init", name: "pig_init_post", methods: ["POST"])]
    public function initCallback(
        Request $request,
        SessionInterface $session
    ): Response {
        $numDice = $request->request->get("num_dice");

        $hand = new DiceHand();
        for ($i = 1; $i <= $numDice; $i++) {
            $hand->add(new DiceGraphic());
        }
        $hand->roll();

        $session->set("pig_dicehand", $hand);
        $session->set("pig_dice", $numDice);
        $session->set("pig_round", 0);
        $session->set("pig_total", 0);
        $session->set("pig_rounds", 0);

        return $this->redirectToRoute("pig_play");
    }

    #[Route("/game/pig/play", name: "pig_play", methods: ["GET"])]
    public function play(SessionInterface $session): Response
    {
        /** @var DiceHand $dicehand */
        $dicehand = $session->get("pig_dicehand");

        $data = [
            "pigDice" => $session->get("pig_dice"),
            "pigRound" => $session->get("pig_round"),
            "pigTotal" => $session->get("pig_total"),
            "diceValues" => $dicehand->getString(),
        ];

        return $this->render("pig/play.html.twig", $data);
    }

    #[Route("/game/pig/roll", name: "pig_roll", methods: ["POST"])]
    public function roll(SessionInterface $session): Response
    {
        /** @var DiceHand $hand */
        $hand = $session->get("pig_dicehand");
        $hand->roll();

        $roundTotal = $session->get("pig_round");
        $round = 0;
        $values = $hand->getValues();
        foreach ($values as $value) {
            if ($value === 1) {
                $round = 0;
                $roundTotal = 0;
                break;
            }
            $round += $value;
        }

        $session->set("pig_round", $roundTotal + $round);
        $session->set("pig_rounds", $session->get("pig_rounds") + 1);

        return $this->redirectToRoute("pig_play");
    }

    #[Route("/game/pig/save", name: "pig_save", methods: ["POST"])]
    public function save(SessionInterface $session): Response
    {
        $roundTotal = $session->get("pig_round");
        $gameTotal = $session->get("pig_total");

        //if roundTotal is 0, then the player has rolled a 1 and lost the round
        if ($roundTotal === 0) {
            $this->addFlash("warning", "Can't save a round with a 1!");
        } else {
            $this->addFlash("notice", "Round saved!");
        }

        $session->set("pig_round", 0);
        $session->set("pig_total", $roundTotal + $gameTotal);

        if ($session->get("pig_total") >= 100) {
            return $this->redirectToRoute("pig_score");
        }

        return $this->redirectToRoute("pig_play");
    }

    #[Route("/game/pig/score", name: "pig_score", methods: ["GET"])]
    public function score(SessionInterface $session): Response
    {
        $data = [
            "pigRounds" => $session->get("pig_rounds"),
            "pigTotal" => $session->get("pig_total"),
        ];

        return $this->render("pig/score.html.twig", $data);
    }
}
