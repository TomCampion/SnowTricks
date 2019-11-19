<?php


namespace App\Service;


use App\Entity\Trick;
use App\Entity\User;

class AccessHelper
{

    public function checkTrickAuthor(Trick $trick, User $user)
    {
        if(in_array('ROLE_ADMIN', $user->getRoles()) != true){
            if(empty($trick) or $trick->getAuthor() !== $user){
                return false;
            }
        }
        return true;
    }
}
