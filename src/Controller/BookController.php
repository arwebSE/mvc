<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Doctrine\ORM\EntityManagerInterface;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Entity\Book;

class BookController extends AbstractController
{
    #[Route("/book/create", name: "book_create")]
    public function createBook(
        Request $request,
        ManagerRegistry $doctrine
    ): Response {
        $entityManager = $doctrine->getManager();

        $book = new Book();
        $form = $this->createForm(BookType::class, $book);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($book);
            $entityManager->flush();

            return new Response("Saved new book with id " . $book->getId());
        }

        return $this->render("book/create.html.twig", [
            "form" => $form->createView(),
        ]);
    }

    #[Route("/book/edit/{bookid}", name: "book_edit")]
    public function editBook(
        Request $request,
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

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute("book_show", [
                "isbn" => $book->getIsbn(),
            ]);
        }

        return $this->render("book/edit.html.twig", [
            "form" => $form->createView(),
        ]);
    }

    #[Route("/book/{isbn}", name: "book_show")]
    public function showBookByIsbn(
        BookRepository $bookRepo,
        string $isbn
    ): Response {
        $book = $bookRepo->findOneByIsbn($isbn);

        if (!$book) {
            throw $this->createNotFoundException(
                "No book found for isbn " . $isbn
            );
        }

        return $this->render("book/index.html.twig", [
            "book" => $book,
        ]);
    }

    #[Route("/book/delete/{isbn}", name: "book_delete", methods: ["POST"])]
    public function deleteBook(
        BookRepository $bookRepo,
        EntityManagerInterface $entityManager,
        string $isbn
    ): Response {
        $book = $bookRepo->findOneByIsbn($isbn);

        if (!$book) {
            throw $this->createNotFoundException(
                "No book found for isbn " . $isbn
            );
        }

        $entityManager->remove($book);
        $entityManager->flush();

        // go back to lib
        return $this->redirectToRoute("library_show");
    }
}
