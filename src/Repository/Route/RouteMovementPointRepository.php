<?php

namespace App\Repository\Route;

use App\Entity\CustomerContractor;
use App\Entity\Route\Route;
use App\Entity\Route\RouteMovementPoint;
use App\Repository\Traits\RandomItemTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RouteMovementPoint|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteMovementPoint|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteMovementPoint[]    findAll()
 * @method RouteMovementPoint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteMovementPointRepository extends ServiceEntityRepository
{
    use RandomItemTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteMovementPoint::class);
    }

    /**
     * Получение списка опозданий.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLateness(
        CustomerContractor $customerContractor,
        int $type,
        \DateTime $dateStart,
        \DateTime $dateStop,
        ?int $intervalStart,
        ?int $intervalStop
    ) {
        $qb = $this->createQueryBuilder('rmp');
        $qb->leftJoin('rmp.route', 'rt');
        $qb->andWhere($qb->expr()->gte('rmp.factDateArrival', ':dateStart'))
            ->andWhere($qb->expr()->lt('rmp.factDateArrival', ':dateStop'))
            ->andWhere($qb->expr()->eq('rmp.type', ':type'))
            ->andWhere($qb->expr()->eq('rmp.latenessFlag', ':flag'))
            ->andWhere($qb->expr()->eq('rt.contractor', ':contractor'))
            ->setParameters([
                'flag' => true,
                'type' => $type,
                'dateStart' => $dateStart,
                'dateStop' => $dateStop,
                'contractor' => $customerContractor->getContractor(),
            ]);
        if ($intervalStart) {
            $qb->andWhere($qb->expr()->gt('rmp.interval', ':intervalStart'))
                ->setParameter('intervalStart', $intervalStart);
        }
        if ($intervalStop) {
            $qb->andWhere($qb->expr()->lte('rmp.interval', ':intervalStop'))
                ->setParameter('intervalStop', $intervalStop);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Получение последней активной точки маршрута.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastPointByRoute(Route $route) {
        $qb = $this->createQueryBuilder('rmp');
        $qb->where('rmp.route = :route');
        $qb->andWhere('rmp.active = :active');
        $qb->orderBy('rmp.rowNumber', 'DESC');
        $qb->setMaxResults(1);
        $qb->setParameters([
            'route' => $route,
            'active' => true
        ]);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
