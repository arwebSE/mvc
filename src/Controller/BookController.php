<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookController extends AbstractController
{
    #[Route("/book", name: "app_book")]
    public function index(): Response
    {
        return $this->render("book/index.html.twig", [
            "controller_name" => "BookController",
        ]);
    }

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
}
