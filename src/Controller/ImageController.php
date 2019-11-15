<?php


namespace App\Controller;


use App\Entity\Image;
use App\Entity\Trick;
use App\Form\ImageType;
use App\Service\AccesHelper;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AccesHelper
     */
    private $accesHelper;

    public function __construct(ObjectManager $em, LoggerInterface $logger, AccesHelper $accesHelper)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->filesystem = new Filesystem();
        $this->accesHelper = $accesHelper;
    }

    /**
     * @Route("/tricks/add_image/{trick_id}", name="add_image")
     * @param Request $request
     * @param $trick_id
     * @return Response
     */
    public function addImage(Request $request, $trick_id): Response
    {
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['id' => $trick_id]);

        if($this->accesHelper->checkTrickAuthor($trick, $this->getUser()) === false){
            $this->redirectToRoute('home');
        }

        $imageForm = $this->createForm(ImageType::class);
        $imageForm->handleRequest($request);

        if($imageForm->isSubmitted() and $imageForm->isValid())
        {
            $image = new Image();
            $safeFilename = str_replace(' ', '', $imageForm['filename']->getData()->getClientOriginalName());

            $image->setFilename($safeFilename);
            $image->setTrick($trick);
            $this->em->persist($image);
            $this->em->flush();

            $this->addTrickImage($trick, $image, $imageForm['filename']->getData(), $safeFilename);

            $this->addFlash(
                "success",
                "Image ajouté !"
            );

            return $this->redirectToRoute('edit_trick',['trick_slug' => $trick->getSlug()]);
        }

        return $this->redirectToRoute('edit_trick',['trick_slug' => $trick->getSlug()]);
    }

    /**
     * @Route("/tricks/edit_image/{image_id}", name="edit_image")
     * @param Request $request
     * @param $image_id
     * @return Response
     */
    public function editImage(Request $request, $image_id): Response
    {
        $image = $this->em->getRepository(Image::class)->findOneBy(['id' => $image_id]);
        $trick= $image->getTrick();

        if($this->accesHelper->checkTrickAuthor($trick, $this->getUser()) === false){
            $this->redirectToRoute('home');
        }

        $imageForm = $this->createForm(ImageType::class);
        $imageForm->handleRequest($request);

        if($imageForm->isSubmitted() and $imageForm->isValid())
        {
            //remove old picture
            $this->deleteTrickImage($trick, $image);

            $safeFilename = str_replace(' ', '', $imageForm['filename']->getData()->getClientOriginalName());

            $image->setFilename($safeFilename);
            $image->setTrick($trick);
            $this->em->persist($image);

            $this->em->flush();

            $this->addTrickImage($trick, $image, $imageForm['filename']->getData(), $safeFilename);

            $this->addFlash(
                "success",
                "Modification de l'image enregistré !"
            );

            return $this->redirectToRoute('edit_trick',['trick_slug' => $trick->getSlug()]);
        }

        $errors = $imageForm->getErrors(true);
        foreach ($errors as $error){
            $this->addFlash(
                "failed",
                $error->getMessage()
            );
        }

        return $this->redirectToRoute('edit_trick',['trick_slug' => $trick->getSlug()]);
    }

    /**
     * @Route("/tricks/delete_image/{image_id}", name="delete_image")
     * @param $image_id
     * @return Response
     */
    public function deleteImage($image_id): Response
    {
        $image = $this->em->getRepository(Image::class)->findOneBy(['id' => $image_id]);
        $trick= $image->getTrick();

        if($this->accesHelper->checkTrickAuthor($trick, $this->getUser()) === false){
            $this->redirectToRoute('home');
        }

        $this->deleteTrickImage($trick, $image);
        $this->em->remove($image);
        $this->em->flush();

        $this->addFlash(
            "success",
            "Suppression de l'image éffectué !"
        );

        return $this->redirectToRoute('edit_trick',['trick_slug' => $trick->getSlug()]);
    }

    private function deleteTrickImage(Trick $trick, Image $image)
    {
        unlink('images/trick/'.$trick->getId().'/'.$image->getFilename());
        $this->filesystem->remove('images/trick/'.$trick->getId().'/'.$image->getFilename());
    }

    private function addTrickImage(Trick $trick, Image $image, $file, $filename)
    {
        try {
            $file->move(
                'images/trick/'.$trick->getId(),
                $image->getFilename()
            );
        } catch (FileException $e) {
            $this->logger->error('erreur changement d\'image du trick '.$trick->getId().' => upload du fichier ' . $filename . ' échoué');
            $this->addFlash(
                "failed",
                "Le site a rencontré un problème, la photo n'a pas pu être téléchargé."
            );
        }
    }

}