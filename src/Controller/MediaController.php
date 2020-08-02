<?php

namespace App\Controller;

use App\Entity\Player;
use App\Form\SearchPlayerType;
use App\Entity\Image;
use App\Entity\Video;
use App\Form\VideoType;
use App\Form\ImageType;
use App\Service\FileUploader;
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
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/media")
 */
class MediaController extends AbstractController
{
    /**
     * Return a player
     * @Route("/{player}", name="media_index", methods={"GET", "POST"})
     */
    public function index(Player $player, Request $request): Response
    {
        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('media/index.html.twig', [
            'player' => $player,
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * Upload image to library, add unique name and add the image to the player
     * @Route("/new/{player}/image", name="media_new_image", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function newImage(
        Request $request,
        FileUploader $fileUploader,
        Player $player,
        EntityManagerInterface $entityManager
    ): Response {

        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $posterFile = $form->get('img')->getData();
            try {
                $posterPath = $fileUploader->upload($posterFile, $image->getName());
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
            $image->setPath($posterPath);
            $entityManager->persist($image);
            $image->addPlayer($player);
            $entityManager->flush();
            return $this->redirectToRoute('media_index', ['player' => $player->getId()]);
        }

        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('media/new_image.html.twig', [
            'image' => $image,
            'player' => $player,
            'form' => $form->createView(),
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * Add the Video to the player
     * @Route("/new/{player}/video", name="media_new_video", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function newVideo(Request $request, Player $player, EntityManagerInterface $entityManager): Response {

        $video = new Video();
        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $video->addPlayer($player);
            $entityManager->persist($video);
            $entityManager->flush();

            return $this->redirectToRoute('media_index', ['player' => $player->getId()]);
        }

        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('media/new_video.html.twig', [
            'video' => $video,
            'form' => $form->createView(),
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * Remove an image from a player
     * @Route("/{image}/{player}", name="media_delete_image", methods={"DELETE"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(Request $request, Image $image, Player $player): Response
    {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $players = $image->getPlayerPosters();
            if (!empty($players)) {
                foreach ($players as $player) {
                    $image->removePlayerPoster($player);
                }
            }
            $players = $image->getPlayers();
            if (!empty($players)) {
                foreach ($players as $player) {
                    $image->removePlayer($player);
                }
            }
            unlink($this->getParameter('image_directory') . '/' . $image->getPath());
            $entityManager->remove($image);
            $entityManager->flush();
        }

        return $this->redirectToRoute('media_index', ['player' => $player->getId()]);
    }
}
