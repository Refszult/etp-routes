<?php

namespace App\Repository\Route;

use App\Classes\StaticStorage\Checks;
use App\Entity\Agreement\Agreement;
use App\Entity\Agreement\Organization;
use App\Entity\CustomerContractor;
use App\Entity\Driver\Driver;
use App\Entity\Route\Route;
use App\Entity\Route\RouteWayPoint;
use App\Entity\Route\Transportation;
use App\Repository\Traits\RandomItemTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @method Route|null find($id, $lockMode = null, $lockVersion = null)
 * @method Route|null findOneBy(array $criteria, array $orderBy = null)
 * @method Route[]    findAll()
 * @method Route[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RouteRepository extends ServiceEntityRepository
{
    use RandomItemTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Route::class);
    }

    public function getLastDriverRoute(Driver $driver)
    {
        $qb = $this->createQueryBuilder('ro');
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->eq('ro.driverOne', ':driver'),
                $qb->expr()->eq('ro.driverTwo', ':driver')
            )
        );
        $qb->andWhere($qb->expr()->isNotNull('ro.closed'));
        $qb->orderBy('ro.updatedOn', 'DESC');
        $qb->setMaxResults(1);
        $qb->setParameter('driver', $driver->getId());

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Получение списка будущих рейсов по тендеру.
     */
    public function findFutureTenderRoutes(\DateTime $tomorrow, int $tenderId, int $routeWayId)
    {
        $qb = $this->createQueryBuilder('ro');
        $qb->andWhere('ro.planDateOfFirstPointLoading >= :tomorrow')
            ->setParameter('tomorrow', $tomorrow, Types::DATETIME_MUTABLE);
        $qb->andWhere('ro.tender = :tender')
            ->setParameter('tender', $tenderId);
        $qb->andWhere('ro.routeWay = :routeWay')
            ->setParameter('routeWay', $routeWayId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Получение связок заказчика и водителя за исключением is_cancel.
     *
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @throws Exception
     */
    public function findByParamsNoCancel(ParameterBag $params, int $customerId, int $contractorId)
    {
        $qb = $this->findByParams($params, $customerId, $contractorId);
        $qb->andWhere($qb->expr()->eq('ro.isCancel', ':cancel'));
        $qb->setParameter('cancel', false);

        return $qb;
    }

    /**
     * Получение рейсов для заказчика.
     */
    public function findContractorsByParams(ParameterBag $params, int $customerId, int $contractorId)
    {
        $qb = $this->findByParams($params, $customerId, $contractorId);
        $qb->orderBy('ro.planDateOfFirstPointArrive', 'ASC');
        $qb->leftJoin('ro.transportation', 'rtr');
        $qb->leftJoin('rtr.organizations', 'org');
        $qb->leftJoin('org.agreements', 'agr');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->isNull('rtr.id'),
            $qb->expr()->neq('rtr.id', Transportation::TYPE_DRIVER_EXPEDITION),
            $qb->expr()->neq('ro.organization', Organization::DRIVER_EXPEDITION_ORGANIZATION),
            $qb->expr()->andX(
                $qb->expr()->eq('rtr.id', Transportation::TYPE_DRIVER_EXPEDITION),
                $qb->expr()->eq('agr.contractor', ':contractor'),
                $qb->expr()->eq('agr.status', Agreement::STATUS_ACTIVE),
                $qb->expr()->eq('agr.isCancel', ':cancel'),
            )
        ));
        $qb->setParameter('cancel', false);

        return $qb;
    }

    /**
     * Получение рейсов для заказчика.
     *
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @throws Exception
     */
    public function findContractorsByParamsNoCancel(ParameterBag $params, int $customerId, int $contractorId)
    {
        $qb = $this->findByParamsNoCancel($params, $customerId, $contractorId);
        $qb->orderBy('ro.planDateOfFirstPointArrive', 'ASC');
        $qb->leftJoin('ro.transportation', 'rtr');
        $qb->leftJoin('rtr.organizations', 'org');
        $qb->leftJoin('org.agreements', 'agr');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->isNull('rtr.id'),
            $qb->expr()->neq('rtr.id', Transportation::TYPE_DRIVER_EXPEDITION),
            $qb->expr()->neq('ro.organization', Organization::DRIVER_EXPEDITION_ORGANIZATION),
            $qb->expr()->andX(
                $qb->expr()->eq('rtr.id', Transportation::TYPE_DRIVER_EXPEDITION),
                $qb->expr()->eq('agr.contractor', ':contractor'),
                $qb->expr()->eq('agr.status', Agreement::STATUS_ACTIVE),
                $qb->expr()->eq('agr.isCancel', ':cancel'),
            )
        ));
        $qb->setParameter('cancel', false);

        return $qb;
    }

    /**
     * Поиск рейсов партнеров подрядчика.
     *
     * @throws Exception
     */
    public function findContractorPartnersByParamsNoCancel(ParameterBag $params, int $customerId, int $contractorId)
    {
        $qbs = $this->_em->createQueryBuilder()
            ->select('co.id')
            ->from(CustomerContractor::class, 'cc');
        $qbs->innerJoin('cc.myPartners', 'pa');
        $qbs->innerJoin('pa.contractor', 'co');
        $qbs->andWhere($qbs->expr()->eq('cc.contractor', ':contractor'));

        $qb = $this->findByParams($params);
        $qb->andWhere($qb->expr()->in('ro.contractor', $qbs->getDQL()));
        $qb->andWhere($qb->expr()->eq('ro.cargoFlow', ':cargoflow'));
        $qb->innerJoin('ro.cargoPipelines', 'cp', Expr\Join::WITH, 'cp.partner = :contractor');
        $qb->andWhere($qb->expr()->eq('ro.isCancel', ':cancel'));
        $qb->setParameter('contractor', $contractorId);
        $qb->setParameter('cargoflow', true);
        $qb->setParameter('cancel', false);

        return $qb;
    }

    /**
     * Получение рейсов для аукционов.
     */
    public function findForAuction(ParameterBag $params)
    {
        $qb = $this->createQueryBuilder('ro');
        $qb->andWhere($qb->expr()->isNull('ro.contractor'));
        $qb->andWhere($qb->expr()->isNull('ro.driverOne'));
        $qb->andWhere($qb->expr()->isNull('ro.driverTwo'));
        // Проверка на название
        $nameExpr = null;
        if (Checks::notEmptyFromString($params->get('name'))) {
            $nameExpr = $qb->expr()->like('lower(ro.routeCode)', ':name');
            $qb->setParameter('name', '%'.mb_strtolower($params->get('name')).'%');
        }
        // Проверка на статус
        if (Checks::notEmptyFromString($params->get('is_draft'))) {
            $qb->andWhere('ro.isDraft = :is_draft')
                ->setParameter('is_draft', true);
        }
        // Проверка на отмену
        if (Checks::notEmptyFromString($params->get('is_cancel'))) {
            $qb->andWhere('ro.isCancel = :is_cancel')
                ->setParameter('is_cancel', false);
        }
        // Проверка на список id
        $idsExpr = null;
        if (Checks::notEmptyFromString($params->get('ids'))) {
            if (is_array($ids = $params->get('ids'))) {
                $idsExpr = $qb->expr()->in('ro.id', ':ids');
                $qb->setParameter('ids', $ids);
            }
        }
        // Проверка на принадлежность к текущему аукциону
        if (Checks::notEmptyFromString($params->get('auction_id'))) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->eq('ro.auction', ':auction'),
                $qb->expr()->isNull('ro.auction')
            ))
            ->setParameter('auction', $params->get('auction_id'));
        } else {
            $qb->andWhere($qb->expr()->isNull('ro.auction'));
        }
        if ($nameExpr && $idsExpr) {
            $qb->andWhere($qb->expr()->orX($nameExpr, $idsExpr));
        } else {
            if ($nameExpr) {
                $qb->andWhere($nameExpr);
            }
            if ($idsExpr) {
                $qb->andWhere($idsExpr);
            }
        }

        return $qb;
    }

    /**
     * Формируем строку запроса для вывода рейсов.
     *
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @throws Exception
     */
    public function findByParams(ParameterBag $params, int $customerId = 0, int $contractorId = 0)
    {
        $qb = $this->createQueryBuilder('ro')
            ->leftJoin('ro.routeWay', 'rw')
            ->orderBy('ro.id', 'DESC');
        //            ->orderBy('ro.isCancel', 'ASC')
        //            ->addOrderBy('rw.code', 'ASC');
        if ($customerId) {
            $qb->andWhere('ro.customer = :customer')
                ->setParameter('customer', $customerId);
        }
        if ($contractorId) {
            $qb->andWhere('ro.contractor = :contractor')
                ->setParameter('contractor', $contractorId);
            $qb->andWhere($qb->expr()->isNotNull('ro.planDateOfFirstPointArrive'));
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('ro.isDraft', ':drrraft'),
                    $qb->expr()->isNotNull('ro.planDateOfFirstPointLoading')
                )
            );
            $qb->setParameter('drrraft', false);
        }
        // Проверка на название
        if (Checks::notEmptyFromString($params->get('name'))) {
            $qb->andWhere(
                $qb->expr()->like('lower(ro.routeCode)', ':name')
            )
                ->setParameter('name', '%'.mb_strtolower($params->get('name')).'%');
        }
        // Проверка на тип рейса
        if ($transportation = $params->getInt('transportation')) {
            $qb->andWhere($qb->expr()->eq('ro.transportation', ':transportation'))
                ->setParameter('transportation', $transportation);
        }
        // Проверка на маршрут
        if (Checks::notEmptyFromString($params->get('route_way'))) {
            $routeWays = [];
            foreach ($params->get('route_way') as $routeWay) {
                if (\intval($routeWay) > 0) {
                    $routeWays[] = $routeWay;
                }
            }
            if ($routeWays) {
                $qb->andWhere($qb->expr()->in('ro.routeWay', ':route_way'))
                    ->setParameter('route_way', $routeWays);
            }
        }
        // Проверка на начальную и конечную точку маршрута рейса
        if (Checks::notEmptyFromString($params->get('first_point'))) {
            $qb->leftJoin('rw.routeWayPoints', 'rwpone');
            $qb->andWhere(
                $qb->expr()->eq('rwpone.department', ':first_point')
            )->setParameter('first_point', $params->getInt('first_point'));
            $qb->andWhere(
                $qb->expr()->eq('rwpone.rowNumber', 1)
            );
        }
        if (Checks::notEmptyFromString($params->get('last_point'))) {
            $qb->leftJoin('rw.routeWayPoints', 'rwptwo');
            $subQb = $this->getEntityManager()->createQueryBuilder()
                ->select('MAX(rwps.rowNumber)')
                ->from(RouteWayPoint::class, 'rwps')
                ->where('rwps.routeWay = rw.id');
            $qb->andWhere(
                $qb->expr()->eq('rwptwo.department', ':last_point')
            )->setParameter('last_point', $params->getInt('last_point'));
            $qb->andWhere(
                $qb->expr()->eq('rwptwo.rowNumber', '('.$subQb->getDQL().')')
            );
        }
        // Проверка на дату рейса
        if (Checks::notEmptyFromString($params->get('date_from'))) {
            $date_from = $params->get('date_from');
            $date_from = \DateTime::createFromFormat('Y-m-d\TH:i', $date_from);
            if ($date_from) {
                $qb->andWhere("AT_TIME_ZONE(AT_TIME_ZONE(ro.planDateOfFirstPointArrive, ro.planDateOfFirstPointArriveTz), 'UTC') >= :date_from")
                    ->setParameter('date_from', $date_from->format('Y-m-d H:i:s'));
            }
        }
        if (Checks::notEmptyFromString($params->get('date_to'))) {
            $date_to = $params->get('date_to');
            $date_to = \DateTime::createFromFormat('Y-m-d\TH:i', $date_to);
            if ($date_to) {
                $qb->andWhere("AT_TIME_ZONE(AT_TIME_ZONE(ro.planDateOfFirstPointArrive, ro.planDateOfFirstPointArriveTz), 'UTC') <= :date_to")
                    ->setParameter('date_to', $date_to->format('Y-m-d H:i:s'));
            }
        }
        if (Checks::notEmptyFromString($params->get('date_first_loading'))) {
            $date_first_loading = new \DateTime($params->get('date_first_loading'));
            if ($date_first_loading) {
                $qb->andWhere('ro.planDateOfFirstPointLoading <= :date_first_loading')
                    ->setParameter('date_first_loading', $date_first_loading, Types::DATETIME_MUTABLE);
            }
        }
        // Проверка на перевозчика
        if (!$contractorId && Checks::notEmptyFromString($params->get('contractor'))) {
            $contractors = [];
            foreach ($params->get('contractor') as $contractor) {
                if (\intval($contractor) > 0) {
                    $contractors[] = $contractor;
                }
            }
            if ($contractors) {
                $qb->andWhere($qb->expr()->in('ro.contractor', ':contractor'))
                    ->setParameter('contractor', $contractors);
            }
        }
        // Проверка по водителям
        if (Checks::notEmptyFromString($params->get('driver'))) {
            $drivers = [];
            foreach ($params->get('driver') as $driver) {
                if (\intval($driver) > 0) {
                    $drivers[] = $driver;
                }
            }
            if ($drivers) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->in('ro.driverOne', ':driver'),
                    $qb->expr()->in('ro.driverTwo', ':driver')
                ))
                    ->setParameter('driver', $drivers);
            }
        }
        // Проверка по ТС
        if (Checks::notEmptyFromString($params->get('vehicle'))) {
            $vehicles = [];
            foreach ($params->get('vehicle') as $vehicle) {
                if (\intval($vehicle) > 0) {
                    $vehicles[] = $vehicle;
                }
            }
            if ($vehicles) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->in('ro.transport', ':vehicle'),
                    $qb->expr()->in('ro.trailer', ':vehicle')
                ))
                    ->setParameter('vehicle', $vehicles);
            }
        }
        // Проверка на менеджера
        if (Checks::notEmptyFromString($params->get('manager'))) {
            $managers = [];
            foreach ($params->get('manager') as $manager) {
                if (\intval($manager) > 0) {
                    $managers[] = $manager;
                }
            }
            $qb->andWhere('ro.naRouteOwner IN(:manager)')
                ->setParameter('manager', $managers);
        }
        // Проверка на статус
        if (Checks::notEmptyFromString($params->get('is_draft'))) {
            $qb->andWhere('ro.isDraft = :is_draft')
                ->setParameter('is_draft', $params->getBoolean('is_draft'));
        }
        // Проверка на отмену
        if (Checks::notEmptyFromString($params->get('is_cancel'))) {
            $qb->andWhere('ro.isCancel = :is_cancel')
                ->setParameter('is_cancel', $params->getBoolean('is_cancel'));
        }
        // Проверка на dirty
        if (Checks::notEmptyFromString($params->get('is_dirty'))) {
            $qb->andWhere('ro.isDirty = :is_dirty')
                ->setParameter('is_dirty', $params->getBoolean('is_dirty'));
        }
        // Проверка на cargo_flow(Грузопровод)
        if (Checks::notEmptyFromString($params->get('cargo_flow'))) {
            $qb->andWhere('ro.cargoFlow = :cargo_flow')
                ->setParameter('cargo_flow', $params->getBoolean('cargo_flow'));
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
        //dd($qb->getDQL());

        return $qb;
    }

    /**
     * Формируем строку запроса для вывода списка маршрутов в аукционах заказчика.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findInCustomerAuctions(int $customerId, $params)
    {
        $qb = $this->createQueryBuilder('ro')
            ->orderBy('ro.id', 'ASC')
            ->leftJoin('ro.auction', 'auction')
            ->leftJoin('auction.customer', 'customer');
        $qb->andWhere(
            $qb->expr()->eq('customer', ':customerId')
        )
            ->setParameter('customerId', $customerId);
        if (Checks::notEmptyFromString($params->get('query'))) {
            $qb->andWhere(
                $qb->expr()->like('lower(ro.routeCode)', ':query')
            )
                ->setParameter('query', '%'.mb_strtolower($params->get('query')).'%');
        }
        if (Checks::notEmptyFromString($params->get('status'))) {
            $statuses = [];
            foreach ($params->get('status') as $status) {
                $statuses[] = \intval($status);
            }
            if (count($statuses) > 0) {
                $qb->andWhere($qb->expr()->in('auction.status', ':status'))
                ->setParameter('status', $statuses);
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

    /**
     * Формируем строку запроса для вывода списка маршрутов в аукционах подрядчиков.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findInContractorAuctions(int $contractorId, $params)
    {
        $qb = $this->createQueryBuilder('ro')
            ->orderBy('ro.id', 'ASC')
            ->leftJoin('ro.auction', 'auction')
            ->leftJoin('auction.possibleParticipants', 'contractor');
        $qb->andWhere(
            $qb->expr()->eq('contractor', ':contractorId')
        )
            ->setParameter('contractorId', $contractorId);
        if (Checks::notEmptyFromString($params->get('query'))) {
            $qb->andWhere(
                $qb->expr()->like('lower(ro.routeCode)', ':query')
            )
                ->setParameter('query', '%'.mb_strtolower($params->get('query')).'%');
        }
        if (Checks::notEmptyFromString($params->get('status'))) {
            $statuses = [];
            foreach ($params->get('status') as $status) {
                $statuses[] = \intval($status);
            }
            if (count($statuses) > 0) {
                $qb->andWhere($qb->expr()->in('auction.status', ':status'))
                    ->setParameter('status', $statuses);
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

    /**
     * Вывод всех рейсов с лимитом.
     *
     * @return mixed
     */
    public function findAllWithLimit(int $limit = 1000, int $offset = 0)
    {
        $qb = $this->createQueryBuilder('r');
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Подсчет количества тендерных рейсов для подрядчика.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountOfTenderRoutesForCustomerContractor(
        int $customerId,
        int $contractorId,
        \DateTime $dateStart,
        \DateTime $dateStop
    ) {
        $qb = $this->createQueryBuilder('ro');
        $qb->select('count(ro.id)');
        $qb->andWhere($qb->expr()->isNotNull('ro.tender'));
        $qb->andWhere($qb->expr()->eq('ro.customer', ':customer'));
        $qb->andWhere($qb->expr()->eq('ro.contractor', ':contractor'));
        $qb->andWhere($qb->expr()->gte('ro.planDateOfFirstPointArrive', ':startdate'));
        $qb->andWhere($qb->expr()->lt('ro.planDateOfFirstPointArrive', ':stopdate'));
        $qb->setParameters([
            'customer' => $customerId,
            'contractor' => $contractorId,
            'startdate' => $dateStart,
            'stopdate' => $dateStop,
        ]);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Количество завершенных рейсов подрядчиком для заказчика.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAccomplishedRoutesCountForCustomerContractor(
        int $customerId,
        int $contractorId,
        \DateTime $startDate,
        \DateTime $stopDate
    ) {
        $qb = $this->createQueryBuilder('r');
        $qb->select('count(r.id)')
            ->andWhere('r.closed = true')
            ->andWhere($qb->expr()->eq('r.customer', ':customer'))
            ->andWhere($qb->expr()->eq('r.contractor', ':contractor'))
            ->andWhere($qb->expr()->gte('r.updatedOn', ':startdate'))
            ->andWhere($qb->expr()->lt('r.updatedOn', ':stopdate'))
            ->setParameters([
                'customer' => $customerId,
                'contractor' => $contractorId,
                'startdate' => $startDate,
                'stopdate' => $stopDate,
            ]);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Количество рейсов, завершенных без опозданий подрядчиком для заказчика.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNoDelayedRoutesCountForCustomerContractor(
        int $customerId,
        int $contractorId,
        \DateTime $startDate,
        \DateTime $stopDate
    ) {
        $qb = $this->createQueryBuilder('r');
        $qb->select('count(r.id)')
            ->andWhere('r.closed = true')
            ->andWhere($qb->expr()->eq('r.customer', ':customer'))
            ->andWhere($qb->expr()->eq('r.contractor', ':contractor'))
            ->andWhere($qb->expr()->gte('r.updatedOn', ':startdate'))
            ->andWhere($qb->expr()->lt('r.updatedOn', ':stopdate'))
            ->andWhere($qb->expr()->eq('r.lateness', 'false'))
            ->setParameters([
                'customer' => $customerId,
                'contractor' => $contractorId,
                'startdate' => $startDate,
                'stopdate' => $stopDate,
            ]);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findByDate(\DateTime $day)
    {
        $qb = $this->createQueryBuilder('ro')
        ;
        $qb->andWhere('ro.planDateOfFirstPointArrive >= :date_from')
            ->andWhere('ro.isCancel = false')
            ->andWhere('ro.isDraft = false')
            ->setParameter('date_from', $day->format('Y-m-d'));

        return $qb->getQuery();
    }

    /**
     * Возвращает укороченные рейсы для определения цены.
     *
     * @param $startPoint
     * @param $endPoint
     *
     * @return int|mixed|string
     */
    public function findShortRoute($startPoint, $endPoint)
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.routeWay', 'rw')
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
