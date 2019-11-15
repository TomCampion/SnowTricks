<?php


namespace App\Controller;

use App\Entity\Trick;
use App\Entity\Video;
use App\Form\VideoType;
use App\Service\AccesHelper;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VideoController extends AbstractController
{

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var AccesHelper
     */
    private $accesHelper;

    public function __construct(ObjectManager $em, AccesHelper $accesHelper)
    {
        $this->em = $em;
        $this->accesHelper = $accesHelper;
    }

    /**
     * @Route("/tricks/add_video/{trick_id}", name="add_video")
     * @param Request $request
     * @param $trick_id
     * @return Response
     */
    public function addVideo(Request $request, $trick_id): Response
    {
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['id' => $trick_id]);

        if($this->accesHelper->checkTrickAuthor($trick, $this->getUser()) === false){
            $this->redirectToRoute('home');
        }

        $videoForm = $this->createForm(VideoType::class);
        $videoForm->handleRequest($request);

        if($videoForm->isSubmitted() and $videoForm->isValid())
        {
            $video = new Video();
            $video->setIframe($videoForm['iframe']->getData());
            $video->setTrick($trick);
            $this->em->persist($video);
            $this->em->flush();

            $this->addFlash(
                "success",
                "Vidéo ajouté"
            );

            return $this->redirectToRoute('edit_trick',['trick_slug' => $trick->getSlug()]);
        }

        return $this->redirectToRoute('edit_trick',['trick_slug' => $trick->getSlug()]);
    }

    /**
     * @Route("/tricks/edit_video/{video_id}", name="edit_video")
     * @param Request $request
     * @param $video_id
     * @return Response
     */
    public function editVideo(Request $request, $video_id): Response
    {
        $video = $this->em->getRepository(Video::class)->findOneBy(['id' => $video_id]);
        $trick = $video->getTrick();

        if($this->accesHelper->checkTrickAuthor($trick, $this->getUser()) === false){
            $this->redirectToRoute('home');
        }

        $videoForm = $this->createForm(VideoType::class);
        $videoForm->handleRequest($request);

        if($videoForm->isSubmitted() and $videoForm->isValid()) {
            $video->setIframe($videoForm['iframe']->getData());
            $this->em->persist($video);
            $this->em->flush();

            $this->addFlash(
                "success",
                "Modification de la vidéo enregistré"
            );

            return $this->redirectToRoute('edit_trick',['trick_slug' => $trick->getSlug()]);
        }

        $errors = $videoForm->getErrors(true);
        foreach ($errors as $error){
            $this->addFlash(
                "failed",
                $error->getMessage()
            );
        }

        return $this->redirectToRoute('edit_trick',['trick_slug' => $trick->getSlug()]);
    }

    /**
     * @Route("/tricks/delete_video/{video_id}", name="delete_video")
     * @param $video_id
     * @return Response
     */
    public function deleteVideo($video_id): Response
    {
        $video = $this->em->getRepository(Video::class)->findOneBy(['id' => $video_id]);
        $trick = $video->getTrick();

        if($this->accesHelper->checkTrickAuthor($trick, $this->getUser()) === false){
            $this->redirectToRoute('home');
        }

        $this->em->remove($video);
        $this->em->flush();

        $this->addFlash(
            "success",
            "Suppression de la vidéo éffectué !"
        );

        return $this->redirectToRoute('edit_trick',['trick_slug' => $trick->getSlug()]);
    }
}