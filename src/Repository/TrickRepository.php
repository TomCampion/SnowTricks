<?php

namespace App\Repository;

use App\Entity\Trick;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Trick|null find($id, $lockMode = null, $lockVersion = null)
 * @method Trick|null findOneBy(array $criteria, array $orderBy = null)
 * @method Trick[]    findAll()
 * @method Trick[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trick::class);
    }

    public function getTricksNumberByUser(User $user)
    {
        $rawSql = 'SELECT COUNT(t.id) FROM trick AS t WHERE t.author_id = '.$user->getId();

        $stmt = $this->getEntityManager()->getConnection()->prepare($rawSql);
        $stmt->execute();

        return $stmt->fetch()["COUNT(t.id)"];
    }
}
