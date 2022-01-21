<?php

namespace App\Repository\Route;

use App\Entity\Route\RouteWay;
use App\Entity\Route\RouteWayPoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RouteWayPoint|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteWayPoint|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteWayPoint[]    findAll()
 * @method RouteWayPoint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteWayPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteWayPoint::class);
    }

    public function findByRouteWay(RouteWay $routeWay)
    {
        return $this->createQueryBuilder('rwp')
            ->andWhere('rwp.routeWay = :routeWay')
            ->setParameter('routeWay', $routeWay)
            ->orderBy('rwp.rowNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
