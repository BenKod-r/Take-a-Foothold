<?php

namespace App\Controller;

use App\Form\SearchPlayerType;
use App\Entity\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PlayerRepository;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends AbstractController
{
    /**
     * Home page display
     * 
     * @Route("/",name="index")
     * @return Response A response instance
     */
    public function index(Request $request, PlayerRepository $playerRepository) :Response
    {
        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('index.html.twig', [
            'players' => $playerRepository->findBy([], ['creationDate' =>'DESC']),
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * Home page display search bar
     * 
     * @Route("/search/{criteria}", name="search_index")
     * @return Response A response instance
     */
    public function search(Request $request, PlayerRepository $playerRepository, string $criteria) :Response
    {
        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);
        $players = $playerRepository->searchPlayer($criteria);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('search.html.twig', [
            'search' => $searchPlayer->createView(),
            'searchPlayer' => $players,
        ]);
    }
}
