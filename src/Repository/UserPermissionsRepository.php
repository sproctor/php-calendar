<?php

namespace App\Repository;

use App\Entity\UserPermissions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPermissions>
 *
 * @method UserPermissions|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserPermissions|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserPermissions[]    findAll()
 * @method UserPermissions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserPermissionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPermissions::class);
    }

    public function save(UserPermissions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserPermissions $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getUserPermissions(int $cid, ?int $uid): ?UserPermissions
    {
        return $this->findOneBy(['cid' => $cid, 'uid' => $uid]);
    }

//    /**
//     * @return UserPermission[] Returns an array of UserPermission objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserPermission
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
