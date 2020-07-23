<?php

namespace App\Controller;

use App\Entity\Player;
use App\Form\PlayerType;
use App\Form\SearchPlayerType;
use App\Form\SearchPlayerAdvancedType;
use App\Repository\ImageRepository;
use App\Entity\Image;
use App\Form\ImageType;
use App\Service\FileUploader;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\CannotWriteFileException;
use Symfony\Component\HttpFoundation\File\Exception\ExtensionFileException;
use Symfony\Component\HttpFoundation\File\Exception\FormSizeFileException;
use Symfony\Component\HttpFoundation\File\Exception\IniSizeFileException;
use Symfony\Component\HttpFoundation\File\Exception\NoFileException;
use Symfony\Component\HttpFoundation\File\Exception\PartialFileException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/player")
 */
class PlayerController extends AbstractController
{
    /**
     * @Route("/", name="player_index", methods={"GET", "POST"})
     */
    public function index(PlayerRepository $playerRepository, Request $request): Response
    {
        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }
        return $this->render('player/index.html.twig', [
            'players' => $playerRepository->findAll(),
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * @Route("/search/advanced", name="search_player", methods={"GET", "POST"})
     */
    public function searchPlayer(PlayerRepository $playerRepository, Request $request): Response
    {
        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);
        $searchPlayerForm = $this->createForm(SearchPlayerAdvancedType::class,);
        $searchPlayerForm->handleRequest($request);
        $players = [];
        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();

            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        if ($searchPlayerForm->isSubmitted() && $searchPlayerForm->isValid()) {
            $criteria = $searchPlayerForm->getData();
            
            $players = $playerRepository->searchPlayerAdvanced($criteria);
        }

        return $this->render('player/search_player.html.twig', [
            'players' => $players,
            'search' => $searchPlayer->createView(),
            'search_form' => $searchPlayerForm->createView(),
        ]);
    }

    /**
     * @Route("/new", name="player_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $player = new Player();
        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($player);
            $entityManager->flush();

            return $this->redirectToRoute('player_choice_poster', ['player' => $player->getId()]);
        }

        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('player/new.html.twig', [
            'player' => $player,
            'form' => $form->createView(),
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * Upload image to library, add unique name and add the image to the player
     * @Route("/new/{player}/addposter", name="player_add_poster", methods={"GET","POST"})
     * 
     */
    public function newWithPoster(
        Request $request,
        FileUploader $fileUploader,
        Player $player,
        EntityManagerInterface $entityManager
    ): Response {

        $poster = new Image();
        $form = $this->createForm(ImageType::class, $poster);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $posterFile = $form->get('img')->getData();
            try {
                $posterPath = $fileUploader->upload($posterFile, $poster->getName());
            } catch (IniSizeFileException | FormSizeFileException $e) {
                $this->addFlash('warning', 'Votre fichier est trop lourd, il ne doit pas dépasser 1Mo.');
                return $this->redirectToRoute('player_add_poster', ['payer' => $player->getId()]);
            } catch (ExtensionFileException $e) {
                $this->addFlash('warning', 'Le format de votre fichier n\'est pas supporté.
                    Votre fichier doit être au format jpeg, jpg ou png.');
                return $this->redirectToRoute('player_add_poster', ['player' => $player->getId()]);
            } catch (PartialFileException | NoFileException | CannotWriteFileException $e) {
                $this->addFlash('warning', 'Fichier non enregistré, veuillez réessayer.
                    Si le problème persiste, veuillez contacter l\'administrateur du site');
                return $this->redirectToRoute('player_add_poster', ['player' => $player->getId()]);
            }
            $poster->setPath($posterPath);
            $entityManager->persist($poster);
            $player->setPoster($poster);
            $entityManager->flush();
            return $this->redirectToRoute('player_index');
        }

        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('player/add_poster.html.twig', [
            'poster' => $poster,
            'form' => $form->createView(),
            'search' => $searchPlayer->createView(),
        ]);
    }

     /**
     * List of all posters, choice of a poster for a player
     * @Route("/new/{player}", name="player_choice_poster", methods={"GET","POST"})
     * 
     */
    public function choicePoster(player $player, ImageRepository $imageRepository, Request $request): Response
    {
        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('player/image.html.twig', [
            'images' => $imageRepository->findAll(),
            'player' => $player,
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * Add a poster to an player and redirection to list of poster
     * @Route("/new/{player}/image/{image}", name="player_new_poster", methods={"GET","POST"})
     * 
     */
    public function addPoster(player $player, Image $image, EntityManagerInterface $entityManager, Request $request): Response
    {
        $player->setPoster($image);
        $entityManager->flush();
        
        return $this->redirectToRoute('player_index');
    }


    /**
     * @Route("/{id}", name="player_show", methods={"GET", "POST"})
     */
    public function show(Player $player, Request $request): Response
    {
        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('player/show.html.twig', [
            'player' => $player,
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="player_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Player $player): Response
    {
        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('player_index');
        }

        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('player/edit.html.twig', [
            'player' => $player,
            'form' => $form->createView(),
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="player_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Player $player): Response
    {
        if ($this->isCsrfTokenValid('delete'.$player->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($player);
            $entityManager->flush();
        }

        return $this->redirectToRoute('player_index');
    }
}
