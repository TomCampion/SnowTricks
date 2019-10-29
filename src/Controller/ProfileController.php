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
     * @param LoggerInterface $logger
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function profile(Request $request, LoggerInterface $logger,  UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = $this->getUser();

        $editProfileForm = $this->createForm(EditProfileType::class, $user);
        $editProfileForm->handleRequest($request);

        $formPassword = $this->createForm(ChangePasswordType::class);
        $formPassword->handleRequest($request);

        $formAvatar = $this->createForm(EditProfilePictureType::class);
        $formAvatar->handleRequest($request);

        if($editProfileForm->isSubmitted() and $editProfileForm->isValid())
        {
            $this->em->flush();
        }

        if($formPassword->isSubmitted() and $formPassword->isValid())
        {
            $this->changePassword($formPassword, $user, $passwordEncoder);
        }

        if($formAvatar->isSubmitted() and $formAvatar->isValid())
        {
            $this->changeAvatar($formAvatar, $user, $logger);
        }

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
            'comments' => $comments
        ]);
    }

    private function changePassword($formPassword, $user, $passwordEncoder)
    {
        if(password_verify($formPassword->getData()['currentPassword'], $user->getPassword()) === true)
        {
            if($formPassword->getData()['newPassword'] === $formPassword->getData()['confirmPassword'])
            {
                $user->setPlainPassword($formPassword->getData()['newPassword']);
                $user->setPassword($passwordEncoder->encodePassword($user, $user->getPlainPassword()));
                $this->em->persist($user);
                $this->em->flush();

                $this->addFlash(
                    "success",
                    "Votre mot de passe a bien été modifié."
                );
            }else
            {
                $this->addFlash(
                    "failed",
                    "Champs nouveau mot de passe et confirmer mot de passe différent."
                );
            }
        }else{
            $this->addFlash(
                "failed",
                "Votre mot de passe actuel ne correpond pas."
            );
        }
    }

    private function changeAvatar($formAvatar, $user, $logger)
    {
        $safeFilename = str_replace(' ','', $formAvatar->getData()['avatar']->getClientOriginalName());

        //remove old picture
        $filesystem = new Filesystem();
        $filesystem->remove('images/profile_picture/'.$user->getId().'/'.$user->getprofilePictureFileName());

        $user->setprofilePictureFileName($safeFilename);
        $this->em->persist($user);
        $this->em->flush();

        try {
            $formAvatar->getData()['avatar']->move(
                'images/profile_picture/'.$user->getId(),
                $safeFilename
            );
        } catch (FileException $e) {
            $logger->error('erreur changement d\'avatar => upload du fichier '.$safeFilename.' échoué pour l\'utilisateur '.$user->getId());
            $this->addFlash(
                "failed",
                "Le site a rencontré un problème, votre avatar n'a pas pu être téléchargé."
            );
        }
    }

}