<?php

namespace App\Repository\Route;

use App\Classes\StaticStorage\Checks;
use App\Entity\Route\RouteWayDimension;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @method RouteWayDimension|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteWayDimension|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteWayDimension[]    findAll()
 * @method RouteWayDimension[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteWayDimensionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteWayDimension::class);
    }

    /**
     * Формируем строку запроса для вывода списка связок точки маршрута - габарит.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findByParams(ParameterBag $params)
    {
        $qb = $this->createQueryBuilder('rwd');
        // Проверка на код или название первой точки
        if (Checks::notEmptyFromString($params->get('firstPoint'))) {
            $qb->leftJoin('rwd.firstPoint', 'fp');
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('lower(fp.code)', ':firstPoint')
//                $qb->expr()->like('lower(fp.name)', ':firstPoint')
            ))
                ->setParameter('firstPoint', '%'.mb_strtolower($params->get('firstPoint')).'%');
        }
        if (Checks::notEmptyFromString($params->get('lastPoint'))) {
            $qb->leftJoin('rwd.lastPoint', 'lp');
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('lower(lp.code)', ':lastPoint')
//                $qb->expr()->like('lower(lp.name)', ':lastPoint')
            ))
                ->setParameter('lastPoint', '%'.mb_strtolower($params->get('lastPoint')).'%');
        }
        if (Checks::notEmptyFromString($params->get('dimensionId'))) {
            $qb->leftJoin('rwd.dimension', 'dim');
            $qb->andWhere($qb->expr()->eq('dim.id', ':dimensionId'))
                ->setParameter('dimensionId', $params->get('dimensionId'));
        }
        if (Checks::notEmptyFromString($params->get('optionalDimensionIds'))) {
            $optionalDimensionIds = [];
            foreach ($params->get('optionalDimensionIds') as $optionalDimensionId) {
                if (\intval((int)$optionalDimensionId) > 0) {
                    $optionalDimensionIds[] = $optionalDimensionId;
                }
            }
            $qb->leftJoin('rwd.optionalDimensions', 'optDims');
//            $qb->leftJoin('optDims.dimension', 'optDim');
            $qb->andWhere($qb->expr()->in('optDims.id', ':optionalDimensionIds'))
                ->setParameter('optionalDimensionIds', $params->get('optionalDimensionIds'));
        }

        return $qb;
    }
}
