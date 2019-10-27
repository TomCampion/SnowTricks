<?php


namespace App\Controller;


use App\Entity\Token;
use App\Entity\User;
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

    public function __construct(ObjectManager $em,\Swift_Mailer $mailer )
    {
        $this->em = $em;
        $this->mailer = $mailer;
    }

    /**
     * @Route("/inscription", name="register")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $token = new Token();
            $token->setValue($this->generateUniqueToken());
            $user->setPassword($passwordEncoder->encodePassword($user, $user->getPlainPassword()));
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