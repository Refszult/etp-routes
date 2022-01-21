<?php

namespace App\Repository\Route;

use App\Classes\StaticStorage\Checks;
use App\Entity\Agreement\Agreement;
use App\Entity\Route\RouteWay;
use App\Entity\Route\RouteWayPoint;
use App\Entity\Route\Transportation;
use App\Repository\Traits\RandomItemTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @method RouteWay|null find($id, $lockMode = null, $lockVersion = null)
 * @method RouteWay|null findOneBy(array $criteria, array $orderBy = null)
 * @method RouteWay[]    findAll()
 * @method RouteWay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteWayRepository extends ServiceEntityRepository
{
    use RandomItemTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RouteWay::class);
    }

    /**
     * Формируем строку запроса для вывода списка маршрутов.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findByParams(ParameterBag $params)
    {
        $qb = $this->createQueryBuilder('rw')
            ->addSelect('length(rw.code) as HIDDEN ln_code')
            ->orderBy('ln_code', 'ASC');
        $qb->andWhere('rw.isCancel = false');
        // Проверка на код или название
        if (null !== $params->get('query')) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('lower(rw.code)', ':query'),
                $qb->expr()->like('lower(rw.name)', ':query')
            ))
                ->setParameter('query', '%'.mb_strtolower($params->get('query')).'%');
        }
        // Проверка на тип перевозки
        if (Checks::notEmptyFromString($params->get('transportation_type'))) {
            $qb->andWhere($qb->expr()->eq('rw.transportationType', ':tt'))
            ->setParameter('tt', $params->getInt('transportation_type'));
        }

        return $qb;
    }

    /**
     * Формируем строку запроса для вывода списка маршрутов в рейсах заказчика.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findByCustomerId(ParameterBag $params)
    {
        $qb = $this->createQueryBuilder('rw')
            ->orderBy('rw.id', 'ASC');
        if (Checks::notEmptyFromString($params->get('name'))) {
            $qb->andWhere(
                $qb->expr()->like('lower(rw.name)', ':name')
            )
                ->setParameter('name', '%'.mb_strtolower($params->get('name')).'%');
        }
        if (Checks::notEmptyFromString($params->get('id'))) {
            $ids = [];
            foreach ($params->get('id') as $id) {
                if (\intval($id) > 0) {
                    $ids[] = $id;
                }
            }
            if ($ids) {
                $qb->andWhere($qb->expr()->in('rw.id', ':ids'))
                  ->setParameter('ids', $ids);
            }
        }

        return $qb;
    }

    /**
     * Формируем строку запроса для вывода списка маршрутов в тендерах заказчика.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findByCustomerIdInTenders(int $customerId, ParameterBag $params)
    {
        $qb = $this->createQueryBuilder('rw')
            ->orderBy('rw.id', 'ASC')
            ->innerJoin('rw.routeTemplates', 'tpl')
            ->innerJoin('tpl.tender', 'te')
            ->innerJoin('te.customer', 'cu');
        $qb->andWhere(
            $qb->expr()->eq('cu.id', ':customerId')
        )
            ->setParameter('customerId', $customerId);
        $qb->andWhere(
            $qb->expr()->eq('rw.active', ':active')
        )->setParameter('active', true);
        if (Checks::notEmptyFromString($params->get('name'))) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('lower(rw.name)', ':name'),
                    $qb->expr()->like('lower(rw.code)', ':name')
                )
            )
                ->setParameter('name', '%'.mb_strtolower($params->get('name')).'%');
        }
        if (Checks::notEmptyFromString($params->get('ids'))) {
            if (is_array($ids = $params->get('ids'))) {
                $qb->andWhere(
                    $qb->expr()->in('rw.id', ':ids')
                )
                    ->setParameter('ids', $ids);
            }
        }

        return $qb;
    }

    /**
     * Формируем строку запроса для вывода списка маршрутов в тендерах подрядчика.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findByContractorIdInTenders(int $contractorId, ParameterBag $params)
    {
        $qb = $this->createQueryBuilder('rw')
            ->orderBy('rw.id', 'ASC')
            ->innerJoin('rw.routeTemplates', 'tpl')
            ->innerJoin('tpl.tender', 'te')
            ->innerJoin('te.winner', 'co');
        $qb->andWhere(
            $qb->expr()->eq('co.id', ':contractorId')
        )
            ->setParameter('contractorId', $contractorId);
        if (Checks::notEmptyFromString($params->get('name'))) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('lower(rw.name)', ':name'),
                    $qb->expr()->like('lower(rw.code)', ':name')
                )
            )
                ->setParameter('name', '%'.mb_strtolower($params->get('name')).'%');
        }
        if (Checks::notEmptyFromString($params->get('ids'))) {
            if (is_array($ids = $params->get('ids'))) {
                $qb->andWhere(
                    $qb->expr()->in('rw.id', ':ids')
                )
                    ->setParameter('ids', $ids);
            }
        }
        $qb->leftJoin('tpl.transportation', 'rtr');
        $qb->leftJoin('rtr.organizations', 'org');
        $qb->leftJoin('org.agreements', 'agr');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->isNull('rtr.id'),
            $qb->expr()->neq('rtr.id', Transportation::TYPE_DRIVER_EXPEDITION),
            $qb->expr()->andX(
                $qb->expr()->eq('rtr.id', Transportation::TYPE_DRIVER_EXPEDITION),
                $qb->expr()->eq('agr.contractor', ':contractorId'),
                $qb->expr()->eq('agr.status', Agreement::STATUS_ACTIVE),
            )
        ));

        return $qb;
    }

    /**
     * Формируем строку запроса для вывода списка маршрутов в рейсах подрядчика.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findByContractorId(int $contractorId, ParameterBag $params)
    {
        $qb = $this->createQueryBuilder('rw')
            ->orderBy('rw.id', 'ASC')
            ->leftJoin('rw.routes', 'route')
            ->leftJoin('route.contractor', 'contractor');
        $qb->andWhere(
            $qb->expr()->eq('contractor', ':contractorId')
        )
            ->setParameter('contractorId', $contractorId);

        if (Checks::notEmptyFromString($params->get('name'))) {
            $qb->andWhere(
                    $qb->expr()->like('lower(rw.name)', ':name')
                )
                    ->setParameter('name', '%'.mb_strtolower($params->get('name')).'%');
        }

        if (Checks::notEmptyFromString($params->get('id'))) {
            $ids = [];
            foreach ($params->get('id') as $id) {
                if (\intval($id) > 0) {
                    $ids[] = $id;
                }
            }
            if ($ids) {
                $qb->andWhere($qb->expr()->in('rw.id', ':ids'))
                 ->setParameter('ids', $ids);
            }
        }

        return $qb;
    }

    /**
     * Возвращает укороченные маршруты для определения цены.
     *
     * @param $startPoint
     * @param $endPoint
     *
     * @return int|mixed|string
     */
    public function findShortRouteWay($startPoint, $endPoint)
    {
        $qb = $this->createQueryBuilder('rw')
            ->leftJoin('rw.routeWayPoints', 'rwpone');
        $qb->andWhere(
            $qb->expr()->eq('rwpone.department', ':startPoint')
        );
        $qb->andWhere(
            $qb->expr()->eq('rwpone.rowNumber', 1)
        );
        $qb->leftJoin('rw.routeWayPoints', 'rwptwo');

        $subSQb = $this->getEntityManager()->createQueryBuilder()
            ->select('MAX(rwps.rowNumber)')
            ->from(RouteWayPoint::class, 'rwps')
            ->where('rwps.routeWay = rw.id');

        $qb->andWhere(
            $qb->expr()->eq('rwptwo.department', ':endPoint')
        );
        $qb->andWhere(
            $qb->expr()->eq('rwptwo.rowNumber', '('.$subSQb->getDQL().')')
        );
        $qb->setParameters([
            'startPoint' => $startPoint,
            'endPoint' => $endPoint,
        ]);

        return $qb->getQuery()->getResult();
    }
}
