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

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    public function __construct(ObjectManager $em, AuthorizationCheckerInterface $authChecker)
    {
        $this->em = $em;
        $this->authChecker = $authChecker;
    }


    /**
     * @Route("/admin/comments", name="admin_comments")
     * @return Response
     */
    public function ShowTricks(): Response
    {
        $comments = $this->em->getRepository(Comment::class)->findAll();

        return $this->render('backend/comments.twig',[
            'comments' => $comments
        ]);
    }

    /**
     * @Route("/tricks/add_comment/{trick_id}", name="add_comment")
     * @param Request $request
     * @param $trick_id
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function addComment(Request $request, $trick_id): Response
    {
        if (false === $this->authChecker->isGranted('ROLE_USER')) {
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

    /**
     * @Route("/tricks/delete_comment/{comment_id}", name="delete_comment")
     * @param Request $request
     * @param $comment_id
     * @return Response
     */
    public function deleteComment(Request $request, $comment_id): Response
    {
        $comment = $this->em->getRepository(Comment::class)->findOneBy(['id' => $comment_id]);
        $trick = $comment->getTrick();

        if($this->authChecker->isGranted('ROLE_ADMIN') === false){
            if(empty($trick) or $trick->getAuthor() !== $this->getUser()){
                $this->redirectToRoute('home');
            }
        }

        $this->em->remove($comment);
        $this->em->flush();

        $this->addFlash(
            "success",
            "Commentaire supprimé !"
        );

        return $this->redirectToRoute('trick_details',['trick_name' => $trick->getName()]);
    }
}