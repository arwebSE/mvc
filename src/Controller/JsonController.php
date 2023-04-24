<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonController
{
    #[Route("/api/quote", name: "quote")]
    public function quoteGenerator(): Response
    {
        $QUOTES = [
            [
                "quote" => "Life isn’t about getting and having, it’s about giving and being.",
                "author" => "Kevin Kruse"
            ],
            [
                "quote" => "Whatever the mind of man can conceive and believe, it can achieve.",
                "author" => "Napoleon Hill"
            ],
            [
                "quote" => "Strive not to be a success, but rather to be of value.",
                "author" => "Albert Einstein"
            ],
            [
                "quote" => "Two roads diverged in a wood, and I—I took the one less traveled by, And that has made all the difference.",
                "author" => "Robert Frost"
            ],
            [
                "quote" => "I attribute my success to this: I never gave or took any excuse.",
                "author" => "Florence Nightingale"
            ],
            [
                "quote" => "You miss 100% of the shots you don’t take.",
                "author" => "Wayne Gretzky"
            ],
            [
                "quote" => "I’ve missed more than 9000 shots in my career. I’ve lost almost 300 games. 26 times I’ve been trusted to take the game winning shot and missed. I’ve failed over and over and over again in my life. And that is why I succeed.",
                "author" => "Michael Jordan"
            ]
        ];

        $rand = random_int(0, count($QUOTES) - 1);
        $quote = $QUOTES[$rand]['quote'];
        $author = $QUOTES[$rand]['author'];

        $datetime = date("Y-m-d H:i:s");

        $data = [
            'datetime' => $datetime,
            'quote' => $quote . " - " . $author,
        ];

        $response = new JsonResponse($data);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        return $response;
    }
}
