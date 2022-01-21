<?php

namespace App\Repository\Route;

use App\Entity\Route\RouteStateLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RouteStateLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteStateLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteStateLog[]    findAll()
 * @method RouteStateLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteStateLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteStateLog::class);
    }
}
