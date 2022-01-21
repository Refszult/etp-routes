<?php

namespace App\Repository\Route;

use App\Entity\Route\RouteWayTransportation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RouteWayTransportation|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteWayTransportation|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteWayTransportation[]    findAll()
 * @method RouteWayTransportation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteWayTransportationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteWayTransportation::class);
    }
}
