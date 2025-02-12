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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
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
            $plainPassword = $user->getPassword();
            $user->setPassword($this->passwordEncoder->encodePassword($user, $plainPassword));
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
            $this->em->getRepository(Token::class)->setNullValue($user->getConfirmationToken()->getId());
            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash(
                "success",
                "Votre compte à bien été activé. Vous pouvez désormais vous connecter !"
            );

            return $this->redirectToRoute('app_login');
        } else {
            return $this->redirectToRoute('app_login');
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
     * @param Request $request
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

            //Check token expiration date
            $days = getenv('FORGOT_PASSWORD_TOKEN_DURATION');
            $maxInterval = date_interval_create_from_date_string("$days days");
            $expirationDate = date_add($user->getPasswordToken()->getCreationDate(),$maxInterval);

            if($expirationDate > new \DateTime())
            {

                if($form->isSubmitted() and $form->isValid())
                {
                    if($form->getData()['newPassword'] === $form->getData()['confirmPassword'])
                    {
                        $plainPassword = $user->getPassword();
                        $user->setPassword($this->passwordEncoder->encodePassword($user, $plainPassword));
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
            }else{
                $this->addFlash(
                    "failed",
                    "La durée d'expiration du lien de réinitialisation du mot de passe à expiré. Vous pouvez recommencer la procédure en cliquant sur Mot de passe oublié."
                );
                return $this->redirectToRoute('app_login');
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

    /**
     * @Route("/admin/users/{page}", name="admin_users")
     * @param int $page
     * @return Response
     */
    public function ShowUsers(int $page = 1): Response
    {
        $nbUsersByPage = getenv('MAX_ELEMENTS_PAR_PAGE_ADMIN');

        $users = $this->em->getRepository(User::class)->findAllPaginate($page, $nbUsersByPage);

        $pagination = array(
            'page' => $page,
            'nbPages' => ceil(count($users) / $nbUsersByPage),
            'nomRoute' => 'admin_users',
            'paramsRoute' => array()
        );

        return $this->render('backend/users.twig',[
            'users' => $users,
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("/users/ban_user/{user_id}", name="ban_user")
     * @param $user_id
     * @param AuthorizationCheckerInterface $authChecker
     * @return Response
     */
    public function banUser($user_id, AuthorizationCheckerInterface $authChecker): Response
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $user_id]);

        if($authChecker->isGranted('ROLE_ADMIN') === false){
            $this->redirectToRoute('home');
        }

        $user->setBan(true);
        $this->em->persist($user);
        $this->em->flush();

        $this->addFlash(
            "success",
            "Utilisateur banni !"
        );

        return $this->redirectToRoute('admin_users');
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