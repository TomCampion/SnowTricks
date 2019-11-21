<?php


namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Form\ImageType;
use App\Form\VideoType;
use App\Service\AccesHelper;
use App\Service\CollectionTypeHelper;
use App\Entity\Trick;
use App\Form\TrickType;
use App\Form\EditTrickType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class TrickController extends AbstractController
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
     * @Route("/admin/tricks/{page}", name="admin")
     * @return Response
     */
    public function ShowTricks(int $page = 1): Response
    {
        $nbTricksByPage = getenv('MAX_ELEMENTS_PAR_PAGE_ADMIN');

        $tricks = $this->em->getRepository(Trick::class)->findAllPaginate($page, $nbTricksByPage);

        $pagination = array(
            'page' => $page,
            'nbPages' => ceil(count($tricks) / $nbTricksByPage),
            'nomRoute' => 'admin',
            'paramsRoute' => array()
        );

        return $this->render('backend/tricks.twig',[
            'tricks' => $tricks,
            'pagination' => $pagination
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
     * @Route("/ajax/tricks", name="ajax_tricks")
     * @param Request $request
     * @return Response
     */
    public function LoadMoreTricks(Request $request)
    {
        $limit = getenv('MAX_TRICKS_LOADED');
        $offset = $request->get('offset');

        $tricks = $this->em->getRepository(Trick::class)->findBy(array(),array(),$limit,$offset);

        $response = $this->render('frontend/trick.twig', array('tricks' => $tricks))->getContent();

        return new JsonResponse($response);
    }

    /**
     * @Route("/tricks/{trick_name}", name="trick_details")
     * @param Request $request
     * @param $trick_name
     * @return Response
     */
    public function tricksDetails(Request $request, $trick_name) : Response
    {
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['name' => $trick_name]);
        $comments = $this->em->getRepository(Comment::class)->findBy(['trick' => $trick]);

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment,[
            'action' => $this->generateUrl('add_comment',['trick_id' => $trick->getId()]),
        ]);

        $form->handleRequest($request);

        return $this->render('frontend/trick_details.twig',[
            'trick' => $trick,
            'comments' => $comments,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/ajouter_trick", name="add_trick")
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
     * @Route("/tricks/editer/{trick_name}", name="edit_trick")
     * @param Request $request
     * @param $trick_name
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function editTrick(Request $request, $trick_name, AuthorizationCheckerInterface $authChecker): Response
    {
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['name' => $trick_name]);

        if($this->accesHelper->checkTrickAuthor($trick, $this->getUser()) === false){
            $this->redirectToRoute('home');
        }

        $form = $this->createForm(EditTrickType::class, $trick);
        $form->handleRequest($request);

        $imageForm = $this->createForm(ImageType::class);
        $imageForm->handleRequest($request);

        $videoForm = $this->createForm(VideoType::class);
        $videoForm->handleRequest($request);

        if($form->isSubmitted() and $form->isValid())
        {

            $this->em->persist($trick);
            $this->em->flush();
            $this->addFlash(
                "success",
                "Modification du trick enregistré !"
            );
            return $this->redirectToRoute('trick_details',['trick_name' => $trick->getName()]);
        }
        dump($trick);
        return $this->render('frontend/edit_trick.twig',[
            'trick' => $trick,
            'form' => $form->createView(),
            'imageForm' => $imageForm,
            'videoForm' => $videoForm
        ]);
    }

    /**
     * @Route("/delete_trick/{trick_id}", name="delete_trick")
     * @param Request $request
     * @param $trick_id
     * @return Response
     */
    public function deleteTrick(Request $request, $trick_id): Response
    {
        $trick = $this->em->getRepository(Trick::class)->findOneBy(['id' => $trick_id]);

        if($this->accesHelper->checkTrickAuthor($trick, $this->getUser()) === false){
            $this->redirectToRoute('home');
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