<?php

namespace App\Repository;

use App\Entity\ForumComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ForumComment>
 *
 * @method ForumComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForumComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForumComment[]    findAll()
 * @method ForumComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumComment::class);
    }

    public function save(ForumComment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ForumComment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllActivatedByPostId($postId)
    {
        return $this->createQueryBuilder('fc')
            ->andWhere('fc.idPost = :postId')
            ->andWhere('fc.isDeleted = false')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllActivatedByUserId($userId)
    {
        return $this->createQueryBuilder('fc')
            ->andWhere('fc.idUser = :userId')
            ->andWhere('fc.isDeleted = false')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult()
            ;
    }

//    /**
//     * @return ForumComment[] Returns an array of ForumComment objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ForumComment
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
