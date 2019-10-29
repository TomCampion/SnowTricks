<?php


namespace App\Controller;


use App\Entity\Token;
use App\Entity\User;
use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Form\UserType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(ObjectManager $em,\Swift_Mailer $mailer, UserPasswordEncoderInterface $passwordEncoder )
    {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/inscription", name="register")
     * @param Request $request
     * @return Response
     */
    public function register(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $token = new Token();
            $token->setValue($this->generateUniqueToken());
            $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPlainPassword()));
            $user->setConfirmationToken($token);
            $this->em->persist($user);
            $this->em->flush();

            $message = (new \Swift_Message('Mail de confirmation'))
                ->setFrom(['contact@tomcampion.fr' => 'Snowtricks'])
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        'emails/registration.twig',
                        [
                            'token' => $user->getConfirmationToken()->getValue(),
                            'username' => $user->getUsername()
                        ]
                    ),
                    'text/html'
                )
            ;
            $this->mailer->send($message);

            $this->addFlash(
                "success",
                "Votre compte à bien été créé. Veuillez confirmer votre inscription via le mail qui vient de vous être envoyé."
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('frontend/register.twig',[
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/activeAccount/{token}/{username}", name="confirm_account")
     * @param $token
     * @param $username
     * @return Response
     */
    public function confirmAccount($token, $username): Response
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        $tokenExist = $user->getConfirmationToken()->getValue();
        if($token === $tokenExist) {
            $user->setIsActive(true);
            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash(
                "success",
                "Votre compte à bien été activé. Vous pouvez désormais vous connecter !"
            );

            return $this->redirectToRoute('app_login');
        } else {
            return $this->render('frontend/login.twig');
        }
    }

    /**
     * @Route("/connexion", name="app_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('frontend/login.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/forgot_password", name="forgot_password")
     * @param Request $request
     * @return Response
     */
    public function forgotPassword(Request $request): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() and $form->isValid())
        {
            $email = $form->getData()['email'];

            $token = new Token();
            $token->setValue($this->generateUniqueToken());

            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

            if(!empty($user))
            {
                $user->setPasswordToken($token);
                $this->em->flush();

                $message = (new \Swift_Message('[Snowtricks] Réinitialisation du mot de passe'))
                    ->setFrom(['contact@tomcampion.fr' => 'Snowtricks'])
                    ->setTo($email)
                    ->setBody(
                        $this->renderView(
                            'emails/forgot_password.twig',
                            [
                                'token' => $user->getPasswordToken()->getValue(),
                                'user' => $user
                            ]
                        ),
                        'text/html'
                    )
                ;
                $this->mailer->send($message);
                $this->addFlash(
                    "success",
                    "Un mail avec un lien de réinitialisation du mot du passe vous a été envoyé !"
                );
                return $this->redirectToRoute('app_login');

            }else{
                $this->addFlash(
                    "failed",
                    "L'adresse email renseigné n'est relié à aucun compte."
                );
            }
        }

        return $this->render('frontend/forgot_password.twig',['form' => $form->createView()]);

    }

    /**
     * @Route("/resetPassword/{token}/{username}", name="reset_password")
     * @param $token
     * @param $username
     * @return Response
     */
    public function resetPassword(Request $request, $token, $username) :Response
    {
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        $tokenExist = $user->getPasswordToken()->getValue();

        if($token === $tokenExist) {
            if($form->isSubmitted() and $form->isValid())
            {
                if($form->getData()['newPassword'] === $form->getData()['confirmPassword'])
                {
                    $user->setPlainPassword($form->getData()['newPassword']);
                    $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPlainPassword()));
                    $this->em->persist($user);
                    $this->em->flush();
                    $this->addFlash(
                        "success",
                        "Votre mot de passe a bien été modifié !"
                    );
                    return $this->redirectToRoute('app_login');
                }else{
                    $this->addFlash(
                        "failed",
                        "Champs nouveau mot de passe et confirmer mot de passe différent."
                    );
                }
            }
        } else {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('frontend/reset_password.twig',['form' => $form->createView()]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    private function generateUniqueToken()
    {
        $token = "";
        while(empty($token)){
            $randomValue = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $repository = $this->em->getRepository(Token::class);
            if($repository->findOneBy(['value'=>$randomValue]) === NULL){
                $token = $randomValue;
            }
        }

        return $token;
    }
}