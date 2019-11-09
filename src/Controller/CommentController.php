<?php


namespace App\Controller;


use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\CommentType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
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
     * @Route("/tricks/add_comment/{trick_id}", name="add_comment")
     * @param Request $request
     * @param $image_id
     * @return Response
     */
    public function addComment(Request $request, $trick_id, AuthorizationCheckerInterface $authChecker): Response
    {
        if (false === $authChecker->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('home');
        }

        $comment = new Comment();
        $user = $this->getUser();
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['id' => $trick_id]);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if($form->isSubmitted() and $form->isValid())
        {
            $comment->setAuthor($user);
            $comment->setTrick($trick);
            $this->em->persist($comment);
            $this->em->flush();

            $this->addFlash(
                "success",
                "Commentaire publié !"
            );
            return $this->redirectToRoute('trick_details',['trick_name' => $trick->getName()]);
        }

        return $this->redirectToRoute('trick_details',['trick_name' => $trick->getName()]);
    }

}