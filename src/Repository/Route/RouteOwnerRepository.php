<?php

namespace App\Repository\Route;

use App\Classes\StaticStorage\Checks;
use App\Entity\Route\RouteOwner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @method RouteOwner|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteOwner|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteOwner[]    findAll()
 * @method RouteOwner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteOwnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteOwner::class);
    }

    /**
     * Формируем строку запроса для вывода списка маршрутов.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findByParams(ParameterBag $params)
    {
        $qb = $this->createQueryBuilder('ro')
            ->leftJoin('ro.agent', 'ag')
            ->orderBy('ro.id', 'ASC');
        // Проверка на код или название
        if (null !== $params->get('query')) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('lower(ro.code)', ':query'),
                $qb->expr()->like('lower(ro.name)', ':query')
            ))
                ->setParameter('query', '%'.mb_strtolower($params->get('query')).'%');
        }
        // Проверка на агента
        if (Checks::notEmptyFromString($params->get('agent'))) {
            $agentIds = [];
            foreach ($params->get('agent') as $id) {
                if (\intval($id) > 0) {
                    $agentIds[] = $id;
                }
            }
            if ($agentIds) {
                $qb->andWhere($qb->expr()->in('ag.id', ':agentIds'))
                    ->setParameter('agentIds', $agentIds);
            }
        }
        if (Checks::notEmptyFromString($params->get('id'))) {
            $ids = [];
            foreach ($params->get('id') as $id) {
                if (\intval($id) > 0) {
                    $ids[] = $id;
                }
            }
            if ($ids) {
                $qb->andWhere($qb->expr()->in('ro.id', ':ids'))
                ->setParameter('ids', $ids);
            }
        }

        return $qb;
    }

    // /**
    //  * @return RouteOwner[] Returns an array of RouteOwner objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RouteOwner
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
