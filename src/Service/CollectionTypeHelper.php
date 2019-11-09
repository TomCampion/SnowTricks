<?php
namespace App\Service;

use App\Entity\Trick;
use Doctrine\ORM\EntityManagerInterface;

class CollectionTypeHelper
{

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getImagesCollectionTypeForm(Trick $trick, $form)
    {

        $tabFiles = [];

        foreach ($form['images'] as $key => $value){

            $image = $value->getData();

            if(!empty($value['filename']->getData())) {
                $safeFilename = str_replace(' ', '', $value['filename']->getData()->getClientOriginalName());
                $image->setFilename($safeFilename);

                $this->em->persist($image);

                $tabFiles[$safeFilename] = $value['filename']->getData();
            }else{
                $trick->removeImage($image);
            }
        }

        return $tabFiles;
    }

}