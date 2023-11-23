<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BookRepository;
use App\Entity\Book;

class LibraryController extends AbstractController
{
    #[Route("/library", name: "library_show")]
    public function showAllBooks(BookRepository $bookRepo): Response
    {
        $books = $bookRepo->findAll();

        return $this->render("library/index.html.twig", [
            "books" => $books,
        ]);
    }

    #[Route("/library/reset", name: "library_reset")]
    public function resetLibrary(
        EntityManagerInterface $entityManager
    ): Response {
        // remove all books
        $entityManager->createQuery("DELETE FROM App\Entity\Book b")->execute();

        $booksData = [
            [
                "title" => "The Hobbit",
                "isbn" => "9780547928227",
                "author" => "J.R.R. Tolkien",
                "image" =>
                    "https://m.media-amazon.com/images/I/710+HcoP38L._AC_UF1000,1000_QL80_.jpg",
            ],
            [
                "title" => "The Lord of the Rings",
                "isbn" => "9780544003415",
                "author" => "J.R.R. Tolkien",
                "image" =>
                    "https://m.media-amazon.com/images/I/71jLBXtWJWL._AC_UF1000,1000_QL80_.jpg",
            ],
            [
                "title" => "Pride and Prejudice",
                "isbn" => "9780679783268",
                "author" => "Jane Austen",
                "image" => "https://www.e-booksdirectory.com/imglrg/373.jpg",
            ],
        ];

        // add to db
        foreach ($booksData as $bookData) {
            $book = new Book();
            $book
                ->setTitle($bookData["title"])
                ->setIsbn($bookData["isbn"])
                ->setAuthor($bookData["author"])
                ->setImage($bookData["image"]);
            $entityManager->persist($book);
        }

        // save db
        $entityManager->flush();

        return $this->render("library/reset.html.twig");
    }
}
