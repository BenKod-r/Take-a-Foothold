<?php

namespace App\Controller;

use App\Entity\Image;
use App\Form\ImageType;
use App\Form\SearchPlayerType;
use App\Repository\ImageRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
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

/**
 * @Route("/image")
 */
class ImageController extends AbstractController
{
    /**
     * @Route("/", name="image_index", methods={"GET", "POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(ImageRepository $imageRepository, Request $request): Response
    {
        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('image/index.html.twig', [
            'images' => $imageRepository->findBy([], ['creationDate' => 'DESC']),
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * Upload image to library, add unique name
     * @Route("/new", name="image_new", methods={"GET","POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(Request $request, FileUploader $fileUploader, EntityManagerInterface $entityManager): Response
    {
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('img')->getData();
            try {
                $imagePath = $fileUploader->upload($imageFile, $image->getName());
            } catch (IniSizeFileException | FormSizeFileException $e) {
                $this->addFlash('warning', 'Votre fichier est trop lourd, il ne doit pas dépasser 1Mo.');
                return $this->redirectToRoute('image_new');
            } catch (ExtensionFileException $e) {
                $this->addFlash('warning', 'Le format de votre fichier n\'est pas supporté.
                    Votre fichier doit être au format jpeg, jpg ou png.');
                return $this->redirectToRoute('image_new');
            } catch (PartialFileException | NoFileException | CannotWriteFileException $e) {
                $this->addFlash('warning', 'Fichier non enregistré, veuillez réessayer.
                    Si le problème persiste, veuillez contacter l\'administrateur du site');
                return $this->redirectToRoute('image_new');
            }
            $image->setPath($imagePath);
            $entityManager->persist($image);
            $entityManager->flush();
            return $this->redirectToRoute('image_index');
        }

        $searchPlayer = $this->createForm(SearchPlayerType::class,);
        $searchPlayer->handleRequest($request);

        if ($searchPlayer->isSubmitted() && $searchPlayer->isValid()) {
            $criteria = $searchPlayer->getData();
            return $this->redirectToRoute('search_index', ['criteria' => $criteria['name']]);
        }

        return $this->render('image/new.html.twig', [
            'image' => $image,
            'form' => $form->createView(),
            'search' => $searchPlayer->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="image_delete", methods={"DELETE"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(Request $request, Image $image): Response
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

        return $this->redirectToRoute('image_index');
    }
}


