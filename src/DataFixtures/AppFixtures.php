<?php

namespace App\DataFixtures;

use App\Entity\Trick;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $json = file_get_contents('Tricks.json');
        $obj = json_decode($json);

        $author = $manager->getRepository(User::class)->findOneBy(["id"=>1]);

        for($i=0; $i< sizeof($obj->tricks); $i++){
            $trick = new Trick();
            $trick->setName($obj->tricks[$i]->name);
            $trick->setCategory($obj->tricks[$i]->category);
            $trick->setDescription($obj->tricks[$i]->description);
            $trick->setDifficulty($obj->tricks[$i]->difficulty);
            $trick->setAuthor($author);

            $manager->persist($trick);

            $manager->flush();
        }

    }
}
