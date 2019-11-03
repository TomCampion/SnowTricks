<?php


namespace App\Controller;


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

    public function __construct(ObjectManager $em )
    {
        $this->em = $em;
    }

    /**
     * @Route("/admin/add_trick", name="add_trick")
     * @param Request $request
     * @return Response
     */
    public function addTrick(Request $request) :Response
    {
        $user = $this->getUser();
        $trick = new Trick();

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);

        if($form->isSubmitted() and $form->isValid())
        {
            $trick->setAuthor($user);
            $this->em->persist($trick);
            $this->em->flush();
        }

        return $this->render('backend/add_trick.twig',[
            'form' => $form->createView()
        ]);

    }

    public function checkAdminRole()
    {
        $user = $this->getUser();

    }

}