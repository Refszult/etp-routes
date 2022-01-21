<?php

namespace App\Repository\Route;

use App\Entity\Route\Route;
use App\Entity\Route\RouteTariff;
use App\Entity\Route\RouteWay;
use App\Entity\Tender\RouteTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RouteTariff|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteTariff|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteTariff[]    findAll()
 * @method RouteTariff[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteTariffRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteTariff::class);
    }

    /**
     * Алгоритм получения тарифа для заданного маршрута, для аукциона
     *
     * @param RouteWay $shortcutRouteWay
     * @param Route $route
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findAuctionRouteTariff(RouteWay $shortcutRouteWay, Route $route)
    {
        $query = $this->createQueryBuilder('rt')
            ->where('rt.routewayGuid = :routewayGuid')
            ->andWhere('rt.contractorGuid = :contractorGuid')
            ->andWhere('rt.cargoFlow = :cargoFlow')
            ->andWhere('rt.boostFlag = :boostFlag')
            ->andWhere('rt.dateStart <= :routeDate');
        $query->andWhere($query->expr()->orX(
                $query->expr()->eq('rt.dateEnd', ':routeDate'),
                $query->expr()->isNull('rt.dateEnd'),
            ))
            ->andWhere()
            ->orderBy('rt.tariffTypeName')
            ->setParameters([
                'routewayGuid' => $shortcutRouteWay->getGuid(),
                'contractorGuid' => '00000000-0000-0000-0000-000000000000',
                'cargoFlow' => $route->getCargoFlow(),
                'boostFlag' => $route->getBoostFlag(),
                'routeDate' => $route->getRouteDate(),
            ])
            ->setMaxResults(1);

        return $query->getQuery()->getOneOrNullResult();
    }

    /**
     * Алгоритм получения тарифа для заданного маршрута, для тендера
     *
     * @param RouteWay $routeWay
     * @param \DateTime|null $dateStart
     * @param \DateTime|null $dateEnd
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findTenderRouteTariff(RouteWay $routeWay, ?\DateTime $dateStart, ?\DateTime $dateEnd)
    {
        $query = $this->createQueryBuilder('rt')
            ->where('rt.routewayGuid = :routewayGuid')
            ->andWhere('rt.contractorGuid = :contractorGuid')
            ->andWhere('rt.routeWayType = :routeWayType')
            ->andWhere('rt.dateStart <= :dateStart');
        if ($dateEnd) {
            $query->andWhere('rt.dateEnd >= :dateEnd');
        } else {
            $query->andWhere($query->expr()->isNull('rt.dateEnd'));
        }
        $query->orderBy('rt.tariffTypeName')
            ->setParameters([
                'routewayGuid' => $routeWay->getGuid(),
                'contractorGuid' => '00000000-0000-0000-0000-000000000000',
                'routeWayType' => $routeWay->getTransportationType()->getGuid(),
                'dateStart' => $dateStart,
            ]);
        if ($dateEnd) {
            $query->setParameter('dateEnd', $dateEnd);
        }
        $query->setMaxResults(1);

        return $query->getQuery()->getOneOrNullResult();
    }
}
