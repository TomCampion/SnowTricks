<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Trick;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function getCommentsNumberByUser(User $user)
    {
        $rawSql = 'SELECT COUNT(c.id) FROM comment AS c WHERE c.author_id = '.$user->getId();

        $stmt = $this->getEntityManager()->getConnection()->prepare($rawSql);
        $stmt->execute();

        return $stmt->fetch()["COUNT(c.id)"];
    }
}
