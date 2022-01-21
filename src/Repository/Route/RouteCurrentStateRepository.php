<?php

namespace App\Repository\Route;

use App\Entity\Route\Route;
use App\Entity\Route\RouteCurrentState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RouteCurrentState|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteCurrentState|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteCurrentState[]    findAll()
 * @method RouteCurrentState[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteCurrentStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteCurrentState::class);
    }

    /**
     * Получение последней активной точки маршрута.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastLogEntryByRoute(Route $route) {
        $qb = $this->createQueryBuilder('rcs');
        $qb->where('rcs.route = :route');
        $qb->orderBy('rmp.createdOn', 'DESC');
        $qb->setMaxResults(1);
        $qb->setParameters([
            'route' => $route
        ]);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
