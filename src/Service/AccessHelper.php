<?php


namespace App\Service;


use App\Entity\Trick;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AccessHelper
{

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    public function __construct(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function checkTrickAuthor(Trick $trick, User $user)
    {
        if($this->authChecker->isGranted('ROLE_ADMIN') === false){
            if(empty($trick) or $trick->getAuthor() !== $user){
                return false;
            }
        }
        return true;
    }
}