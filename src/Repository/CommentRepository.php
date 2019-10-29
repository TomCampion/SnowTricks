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
        $qb = $this->createQueryBuilder('c');
        $qb->select('count(c.id)');
        $qb->from(Comment::class,'t');
        $qb->where('c.author ='.$user->getId());

        $count = $qb->getQuery()->getSingleScalarResult();
        return $count;
    }
}
