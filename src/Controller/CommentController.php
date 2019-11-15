<?php


namespace App\Controller;


use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\CommentType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
     * @Route("/admin/comments/{page}", name="admin_comments")
     * @param int $page
     * @return Response
     */
    public function ShowComments(int $page = 1): Response
    {
        $nbCommentsByPage = getenv('MAX_ELEMENTS_PAR_PAGE_ADMIN');

        $comments = $this->em->getRepository(Comment::class)->findAllPaginate($page, $nbCommentsByPage);

        $pagination = array(
            'page' => $page,
            'nbPages' => ceil(count($comments) / $nbCommentsByPage),
            'nomRoute' => 'admin_comments',
            'paramsRoute' => array()
        );

        return $this->render('backend/comments.twig',[
            'comments' => $comments,
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("/ajax/comments", name="ajax_comments")
     * @param Request $request
     * @return Response
     */
    public function LoadMoreComments(Request $request): Response
    {
        $limit = getenv('MAX_COMMENT_LOADED');
        $offset = $request->get('offset');

        $comments = $this->em->getRepository(Comment::class)->findBy(array(),array(),$limit,$offset);

        $response = $this->render('frontend/comment.twig', array('comments' => $comments))->getContent();

        return new Response($response);
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

        $errors = $form->getErrors(true);
        foreach ($errors as $error){
            $this->addFlash(
                "failed",
                $error->getMessage()
            );
        }

        return $this->redirectToRoute('trick_details',['trick_name' => $trick->getName()]);
    }

    /**
     * @Route("/tricks/delete_comment/{comment_id}", name="delete_comment")
     * @param $comment_id
     * @return Response
     */
    public function deleteComment($comment_id): Response
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