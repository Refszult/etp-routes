<?php

namespace App\Service\Route;

use App\Classes\Api\Pagination;
use App\Classes\RequestResponse\ServiceResponse;
use App\Dto\Dimension\ApiDimensionCalculateDto;
use App\Dto\Route\ApiRouteWayDimensionDto;
use App\Entity\Route\RouteWay;
use App\Entity\Route\RouteWayDimension;
use App\Entity\Vehicle\Dimension;
use App\Entity\Vehicle\DimensionCalculate;
use App\Security\Voter\Route\RouteWayDimensionVoter;
use App\Service\Base\BaseDtoService;
use App\Service\Route\Actions\ActionsRouteWayDimensionService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class RouteWayDimensionService.
 */
class RouteWayDimensionService extends BaseDtoService
{
    private ActionsRouteWayDimensionService $routeWayDimensionService;

    /**
     * {@inheritdoc}
     */
    public function onConstruct()
    {
        $this->routeWayDimensionService = $this->container->get(ActionsRouteWayDimensionService::class);
    }

    /**
     * Создание новой связки точки маршрута - габариты ТС.
     */
    public function createRouteWayDimension(
        int $customerId,
        ApiRouteWayDimensionDto $routeWayDimensionDto
    ): ServiceResponse {
        $routeWayDimension = null;
        $this->denyAccessUnlessGranted('ROUTE_WAY_DIMENSION_CREATE', $customerId);
        $this->response->addErrors($this->validator->validate($routeWayDimensionDto));
        if (!$this->response->hasErrors()) {
            $routeWayDimension = $this->routeWayDimensionService->createRouteWayDimension($routeWayDimensionDto);
            $this->response->addErrors($this->validator->validate($routeWayDimension));
            if (!$this->response->hasErrors()) {
                $this->entityManager->persist($routeWayDimension);
                $this->entityManager->flush();
            }
        }

        return $this->prepareResponse($this->response, $routeWayDimension);
    }

    /**
     * Обновление связки точки маршрута - габариты ТС.
     */
    public function updateRouteWayDimension(
        int $customerId,
        int $routeWayDimensionId,
        ApiRouteWayDimensionDto $routeWayDimensionDto
    ): ServiceResponse {
        /** @var RouteWayDimension $routeWayDimension */
        $routeWayDimension = $this->entityManager->getRepository(RouteWayDimension::class)
            ->find($routeWayDimensionId);
        $this->denyAccessUnlessGranted('ROUTE_WAY_DIMENSION_UPDATE', $routeWayDimension);
        if (!$routeWayDimension) {
            throw new NotFoundHttpException('Не найдена связка точек маршрута и габарита');
        }
        $this->response->addErrors($this->validator->validate($routeWayDimensionDto));
        if (!$this->response->hasErrors()) {
            $routeWayDimension = $this->routeWayDimensionService->updateRouteWayDimension(
                $routeWayDimension,
                $routeWayDimensionDto
            );
            $this->response->addErrors($this->validator->validate($routeWayDimension));
            if (!$this->response->hasErrors()) {
                $this->entityManager->flush();
            }
        }

        return $this->prepareResponse($this->response, $routeWayDimension);
    }

    /**
     * Обновление связки точки маршрута - габариты ТС.
     */
    public function deleteRouteWayDimension(
        int $customerId,
        int $routeWayDimensionId
    ): ServiceResponse {
        /** @var RouteWayDimension $routeWayDimension */
        $routeWayDimension = $this->entityManager->getRepository(RouteWayDimension::class)
            ->find($routeWayDimensionId);
        $this->denyAccessUnlessGranted('ROUTE_WAY_DIMENSION_DELETE', $routeWayDimension);
        if (!$routeWayDimension) {
            throw new NotFoundHttpException('Не найдена связка точек маршрута и габарита');
        }
        $this->entityManager->remove($routeWayDimension);
        $this->entityManager->flush();

        return $this->prepareResponse($this->response, null);
    }

    /**
     * Получение связки точки маршрута - габариты ТС.
     */
    public function getRouteWayDimension(
        int $customerId,
        int $routeWayId
    ): ServiceResponse {
        /** @var RouteWay $routeWay */
        $routeWay = $this->entityManager->getRepository(RouteWay::class)
            ->find($routeWayId);
        $this->denyAccessUnlessGranted('ROUTE_WAY_DIMENSION_GET', $routeWay);
        if (!$routeWay) {
            throw new NotFoundHttpException('Указанный маршрут не найден.');
        }

        $routeWayDimension = $this->entityManager->getRepository(RouteWayDimension::class)->findOneBy(
            [
                'firstPoint' => $routeWay->getFirstRouteWayPoint()->getDepartment(),
                'lastPoint' => $routeWay->getLastRouteWayPoint()->getDepartment(),
            ]
        );
        if (!$routeWayDimension) {
            $routeWayDimension = new RouteWayDimension();
            $defaultDimensions = $this->entityManager->getRepository(Dimension::class)->findDefaultDimensions();
            /** @var Dimension $dimension */
            foreach ($defaultDimensions as $dimension) {
                if ($dimension->getIsDefault()) {
                    $routeWayDimension->setDimension($dimension);
                    continue;
                }
                $routeWayDimension->addOptionalDimension($dimension);
            }
        }
        foreach ($routeWayDimension->getOptionalDimensions() as $optionalDimension) {
            /** @var DimensionCalculate $dimensionCalculate */
            $dimensionCalculate = $this->entityManager->getRepository(DimensionCalculate::class)->findOneBy(
                [
                    'mainDimension' => $routeWayDimension->getDimension(),
                    'optionalDimension' => $optionalDimension,
                ]
            );
            if (null !== $dimensionCalculate) {
                $optionalDimension->setPercentage($dimensionCalculate->getPercentage());
            }
        }

        return $this->prepareResponse($this->response, $routeWayDimension);
    }

    /**
     * Получение связки точки маршрута - габариты ТС по id.
     */
    public function getRouteWayDimensionById(
        int $customerId,
        int $routeWayDimensionId
    ): ServiceResponse {
        /** @var RouteWayDimension $routeWayDimension */
        $routeWayDimension = $this->entityManager->getRepository(RouteWayDimension::class)
            ->find($routeWayDimensionId);
        $this->denyAccessUnlessGranted('ROUTE_WAY_DIMENSION_GET', $routeWayDimension);
        if (!$routeWayDimension) {
            throw new NotFoundHttpException('Указанная связка не найдена.');
        }

        return $this->prepareResponse($this->response, $routeWayDimension);
    }

    /**
     * Получение связок точки маршрута - габариты ТС.
     */
    public function getRouteWayDimensionList(
        int $customerId,
        ParameterBag $params
    ): ServiceResponse {
        $this->denyAccessUnlessGranted('ROUTE_WAY_DIMENSION_GET');
        $queryBuilder = $this->entityManager->getRepository(RouteWayDimension::class)->findByParams($params);
        $pagination = new Pagination(
            $this->paginator->paginate(
                $queryBuilder,
                $params->getInt('page', 1),
                $params->getInt('limit', 10)
            )
        );

        return $this->prepareResponse($this->response, $pagination);
    }

    /**
     * Получение коэффициентов отношения опциональных габаритов к основному.
     */
    public function getDimensionCalculatesByMainDimension(
        int $customerId,
        int $dimensionId,
        ParameterBag $params
    ): ServiceResponse {
        $this->denyAccessUnlessGranted('DIMENSION_CALCULATE_GET');
        $queryBuilder = $this->entityManager->getRepository(DimensionCalculate::class)->findByParams(
            $params,
            $dimensionId
        );
        $pagination = new Pagination(
            $this->paginator->paginate(
                $queryBuilder,
                $params->getInt('page', 1),
                $params->getInt('limit', 10)
            )
        );

        return $this->prepareResponse($this->response, $pagination);
    }

    /**
     * Получение коэффициентов отношения опциональных габаритов к основному.
     */
    public function getDimensionCalculateList(
        ParameterBag $params
    ): ServiceResponse {
        $this->denyAccessUnlessGranted('DIMENSION_CALCULATE_GET');
        $queryBuilder = $this->entityManager->getRepository(DimensionCalculate::class)->findAll();
        $pagination = new Pagination(
            $this->paginator->paginate(
                $queryBuilder,
                $params->getInt('page', 1),
                $params->getInt('limit', 10)
            )
        );

        return $this->prepareResponse($this->response, $pagination);
    }

    /*
     * Создание/обновление коэфициента отношения габаритов по основному габариту.
     */
    public function updateDimensionCalculatesByMainDimension(
        int $mainDimensionId,
        ArrayCollection $dimensionCalculateDtoCollection
    ): ServiceResponse {
        $this->denyAccessUnlessGranted(RouteWayDimensionVoter::DIMENSION_CALCULATE_UPDATE);
        $dimensionCalculateCollection = new ArrayCollection();

        $this->response->addErrors(
            $this->validator->validate(
                $dimensionCalculateDtoCollection,
                null,
                'Create_update_dimension_calculate'
            )
        );

        if (!$this->response->hasErrors()) {
            /* @var ApiDimensionCalculateDto $dimensionCalculateDto */
            foreach ($dimensionCalculateDtoCollection as $dimensionCalculateDto) {
                $dimensionCalculate = $this->entityManager
                    ->getRepository(DimensionCalculate::class)->findOneBy(
                        [
                            'mainDimension' => $mainDimensionId,
                            'optionalDimension' => $dimensionCalculateDto->getOptionalDimension()->getId(),
                        ]
                    );

                if (null === $dimensionCalculate) {
                    $dimensionCalculate = new DimensionCalculate();
                    $dimensionCalculateDto->createFieldSet($dimensionCalculate);
                    $this->entityManager->persist($dimensionCalculate);
                } else {
                    $this->entityManager->getUnitOfWork()->markReadOnly($dimensionCalculate->getMainDimension());
                    $dimensionCalculateDto->updateFieldSet($dimensionCalculate);
                }
                $dimensionCalculateCollection->add($dimensionCalculate);
            }
            $this->response->addErrors($this->validator->validate($dimensionCalculateCollection));
            if (!$this->response->hasErrors()) {
                $this->entityManager->flush();
            }
        }

        return $this->prepareResponse($this->response, $dimensionCalculateCollection);
    }
}
