<?php


namespace App\Controller;

use App\Service\CollectionTypeHelper;
use App\Entity\Trick;
use App\Form\TrickType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrickController extends AbstractController
{
    /**
     * @var ObjectManager
     */
    private $em;

    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin", name="admin")
     * @return Response
     */
    public function ShowTricks(): Response
    {
        $tricks = $this->em->getRepository(Trick::class)->findAll();

        return $this->render('backend/tricks.twig',[
            'tricks' => $tricks
        ]);
    }

    /**
     * @Route("/", name="home")
     * @return Response
     */
    public function index(): Response
    {
        $tricks = $this->em->getRepository(Trick::class)->findAll();

        return $this->render('frontend/home.twig',[
            'tricks' => $tricks
        ]);
    }

    /**
     * @Route("/add_trick", name="add_trick")
     * @param Request $request
     * @param CollectionTypeHelper $collectionTypeHelper
     * @return Response
     */
    public function addTrick(Request $request, CollectionTypeHelper $collectionTypeHelper): Response
    {
        $user = $this->getUser();
        $trick = new Trick();

        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);

        if($form->isSubmitted() and $form->isValid())
        {
            $trick->setAuthor($user);
            $this->em->persist($trick);

            $tabImageFiles = $collectionTypeHelper->getImagesCollectionTypeForm($trick, $form);
            $collectionTypeHelper->persistVideosCollectionTypeForm($trick, $form);

            $this->em->flush();

            foreach ($tabImageFiles as $key => $value)
            {
                $value->move(
                    'images/trick/'.$trick->getId(),
                    $key
                );
            }

            $this->addFlash(
                "success",
                "Nouveau trick enregistré !"
            );
            return $this->redirectToRoute('home');

        }

        return $this->render('frontend/add_trick.twig',[
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/edit_trick/{trick_id}", name="edit_trick")
     * @param Request $request
     * @param $trick_id
     * @return Response
     */
    public function editTrick(Request $request, $trick_id): Response
    {
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['id' => $trick_id]);

        if(empty($trick) or $trick->getAuthor() != $this->getUser() ){
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);

        if($form->isSubmitted() and $form->isValid())
        {
            $trick->setUpdateDate(new \DateTime());
            $this->em->persist($trick);
            $this->em->flush();
            $this->addFlash(
                "success",
                "Modification du trick enregistré !"
            );
            return $this->redirectToRoute('admin');
        }

    }

    /**
     * @Route("/admin/delete_trick/{trick_id}", name="delete_trick")
     * @param Request $request
     * @param $trick_id
     * @return Response
     */
    public function deleteTrick(Request $request, $trick_id): Response
    {
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['id' => $trick_id]);

        if(empty($trick) or $trick->getAuthor() != $this->getUser() ){
            return $this->redirectToRoute('home');
        }

        $this->em->remove($trick);
        $this->em->flush();

        $this->addFlash(
             "success",
             "Suppression du trick effectué avec succès !"
        );

        return $this->redirectToRoute('home');
    }

}