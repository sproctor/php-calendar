<?php

namespace App\Repository;

use App\Entity\User;
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
    private array $cache = [];

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

    public function getUserPermissions(int $cid, ?User $user): UserPermissions
    {
        $uid = $user?->getUid();
        if (isset($this->cache["$cid:$uid"])) {
            return $this->cache["$cid:$uid"];
        }
        $permissions = $this->findOneBy(['cid' => $cid, 'uid' => $uid]);
        if ($user !== null) {
            $default_permissions = $this->findOneBy(['cid' => $cid, 'uid' => null]);
            if ($default_permissions !== null) {
                $permissions->setRead($permissions->canRead() || $default_permissions->canRead());
                $permissions->setCreate($permissions->canCreate() || $default_permissions->canCreate());
                $permissions->setUpdate($permissions->canUpdate() || $default_permissions->canUpdate());
                $permissions->setModerate($permissions->canModerate() || $default_permissions->canModerate());
                $permissions->setAdmin($permissions->canAdmin() || $default_permissions->canAdmin());
            }
        }
        if ($permissions === null) {
            $permissions = new UserPermissions($cid, $uid);
        }
        if ($user?->isAdmin()) {
            $permissions->setRead(true);
            $permissions->setCreate(true);
            $permissions->setUpdate(true);
            $permissions->setModerate(true);
            $permissions->setAdmin(true);
        }
        $this->cache["$cid:$uid"] = $permissions;
        return $permissions;
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
