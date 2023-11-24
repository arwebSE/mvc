<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\Persistence\ManagerRegistry;

use App\Repository\BookRepository;
use App\Entity\Book;

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

    #[Route("/api/library/books", name: "book_show_all")]
    public function showAllBooks(BookRepository $bookRepo): Response
    {
        $books = $bookRepo->findAll();

        $response = $this->json($books);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );
        return $response;
    }

    #[Route("/api/library/book/{isbn}", name: "book_by_isbn")]
    public function showBookById(
        BookRepository $bookRepo,
        string $isbn
    ): Response {
        $book = $bookRepo->findOneByIsbn($isbn);

        if (!$book) {
            return $this->json(
                ["message" => "Book not found"],
                Response::HTTP_NOT_FOUND
            );
        }

        $response = $this->json($book, Response::HTTP_OK);
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT
        );
        return $response;
    }

    #[Route("/api/library/book/delete/{bookid}", name: "book_delete_by_id")]
    public function deleteBookById(
        ManagerRegistry $doctrine,
        int $bookid
    ): Response {
        $entityManager = $doctrine->getManager();
        $book = $entityManager->getRepository(Book::class)->find($bookid);

        if (!$book) {
            throw $this->createNotFoundException(
                "No book found for id " . $bookid
            );
        }

        $entityManager->remove($book);
        $entityManager->flush();

        return $this->redirectToRoute("book_show_all");
    }
}
