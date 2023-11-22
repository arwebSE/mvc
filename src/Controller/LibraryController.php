<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LibraryController extends AbstractController
{
    #[Route("/library", name: "library_index")]
    public function home(): Response
    {
        return $this->render("library/index.html.twig");
    }
}
