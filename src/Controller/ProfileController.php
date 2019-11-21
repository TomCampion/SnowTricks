<?php


namespace App\Controller;


use App\Entity\Comment;
use App\Entity\Trick;
use App\Form\ChangePasswordType;
use App\Form\EditProfilePictureType;
use App\Form\EditProfileType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ProfileController extends AbstractController
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
     * @Route("/profil", name="profile")
     * @param Request $request
     * @return Response
     */
    public function profile(Request $request): Response
    {
        $user = $this->getUser();

        $editProfileForm = $this->createForm(EditProfileType::class, $user,[
            'action' => $this->generateUrl('edit_profile'),
        ]);
        $editProfileForm->handleRequest($request);

        $formPassword = $this->createForm(ChangePasswordType::class, null,[
            'action' => $this->generateUrl('change_password'),
        ]);
        $formPassword->handleRequest($request);

        $formAvatar = $this->createForm(EditProfilePictureType::class, null,[
            'action' => $this->generateUrl('change_avatar'),
        ]);
        $formAvatar->handleRequest($request);

        $trickRepository = $this->em->getRepository(Trick::class);
        $tricksNumber = $trickRepository->getTricksNumberByUser($user);

        $commentRepository = $this->em->getRepository(Comment::class);
        $commentNumber = $commentRepository->getCommentsNumberByUser($user);

        $tricks = $user->getTricks();
        $comments = $user->getComments();

        return $this->render('frontend/profile.twig', [
            'form' => $editProfileForm->createView(),
            'formPassword' => $formPassword->createView(),
            'formAvatar' => $formAvatar->createView(),
            'trickNumber' => $tricksNumber,
            'commentNumber' => $commentNumber,
            'tricks' => $tricks,
            'comments' => $comments,
        ]);
    }

    /**
     * @Route("/editProfile", name="edit_profile")
     * @param Request $request
     * @return Response
     */
    public function editProfile(Request $request) :Response
    {

        $user = $this->getUser();


        $editProfileForm = $this->createForm(EditProfileType::class, $user);
        $editProfileForm->handleRequest($request);

        if($editProfileForm->isSubmitted() and $editProfileForm->isValid())
        {
           $this->em->flush();
        }

        $errors = $editProfileForm->getErrors(true);
        foreach ($errors as $error){
            $this->addFlash(
                "failed_profile",
                $error->getMessage()
            );
        }

        return $this->redirectToRoute('profile');
    }

    /**
     * @Route("/changePassword", name="change_password")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function changePassword(UserPasswordEncoderInterface $passwordEncoder, Request $request) :Response
    {
        $user = $this->getUser();

        $formPassword = $this->createForm(ChangePasswordType::class);
        $formPassword->handleRequest($request);

        if($formPassword->isSubmitted() and $formPassword->isValid()) {

            if (password_verify($formPassword->getData()['currentPassword'], $user->getPassword()) === true) {
                if ($formPassword->getData()['newPassword'] === $formPassword->getData()['confirmPassword']) {
                    $plainPassword = $user->getPassword();
                    $user->setPassword($passwordEncoder->encodePassword($user, $plainPassword));
                    $this->em->persist($user);
                    $this->em->flush();

                    $this->addFlash(
                        "success",
                        "Votre mot de passe a bien été modifié."
                    );

                    $this->redirectToRoute('profile');

                } else {
                    $this->addFlash(
                        "failed",
                        "Champs nouveau mot de passe et confirmer mot de passe différent."
                    );
                }
            } else {
                $this->addFlash(
                    "failed",
                    "Votre mot de passe actuel ne correpond pas."
                );
            }
        }

        return $this->redirectToRoute('profile');
    }

    /**
     * @Route("/changeAvatar", name="change_avatar")
     * @param LoggerInterface $logger
     * @param Request $request
     * @return Response
     */
    public function changeAvatar( LoggerInterface $logger, Request $request): Response
    {
        $user = $this->getUser();

        $formAvatar = $this->createForm(EditProfilePictureType::class);
        $formAvatar->handleRequest($request);

        if($formAvatar->isSubmitted() and $formAvatar->isValid()) {

            $safeFilename = str_replace(' ', '', $formAvatar->getData()['avatar']->getClientOriginalName());

            //remove old picture
            $filesystem = new Filesystem();
            $filesystem->remove('images/profile_picture/' . $user->getId() . '/' . $user->getprofilePictureFileName());

            $user->setprofilePictureFileName($safeFilename);
            $this->em->persist($user);
            $this->em->flush();

            try {
                $formAvatar->getData()['avatar']->move(
                    'images/profile_picture/' . $user->getId(),
                    $safeFilename
                );
            } catch (FileException $e) {
                $logger->error('erreur changement d\'avatar => upload du fichier ' . $safeFilename . ' échoué pour l\'utilisateur ' . $user->getId());
                $this->addFlash(
                    "failed",
                    "Le site a rencontré un problème, votre avatar n'a pas pu être téléchargé."
                );
            }
        }

        $errors = $formAvatar->getErrors(true);
        foreach ($errors as $error){
            $this->addFlash(
                "failed_avatar",
                $error->getMessage()
            );
        }

        return $this->redirectToRoute('profile');
    }

}