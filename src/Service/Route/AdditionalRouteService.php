<?php

namespace App\Service\Route;

use App\Classes\Api\Pagination;
use App\Classes\RequestResponse\ServiceResponse;
use App\Dto\Route\ApiTransportationSettingsDto;
use App\Entity\Route\CargoPipelineEvent;
use App\Entity\Route\CargoPipelinePlacesOfEvent;
use App\Entity\Route\Transportation;
use App\Entity\Route\TransportationSettings;
use App\Entity\Route\TransportationType;
use App\Security\Voter\Route\AdditionalRouteVoter;
use App\Service\Base\BaseDtoService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AdditionalRouteService.
 */
class AdditionalRouteService extends BaseDtoService
{
    /**
     * Получение списка моделей ТС.
     */
    public function getTransportationTypeList(ParameterBag $params): ServiceResponse
    {
        $this->denyAccessUnlessGranted('VEHICLE_MODELS_LIST');
        $passportTypesList = $this->entityManager->getRepository(TransportationType::class)
            ->findByParams($params);
        $pagination = new Pagination(
            $this->paginator->paginate(
                $passportTypesList,
                $params->getInt('page', 1),
                $params->getInt('limit', 10)
            )
        );

        return $this->prepareResponse($this->response, $pagination);
    }

    /**
     * Получение списка событий грузопровода.
     */
    public function getCargoPipelineEvents(ParameterBag $params): ServiceResponse
    {
        $this->denyAccessUnlessGranted('CARGO_PIPELINE_EVENT_LIST');
        $passportTypesList = $this->entityManager->getRepository(CargoPipelineEvent::class)
            ->findAll();
        $pagination = new Pagination(
            $this->paginator->paginate(
                $passportTypesList,
                $params->getInt('page', 1),
                $params->getInt('limit', 10)
            )
        );

        return $this->prepareResponse($this->response, $pagination);
    }

    /**
     * Получение списка событий грузопровода.
     */
    public function getCargoPipelinePlacesOfEvent(ParameterBag $params): ServiceResponse
    {
        $this->denyAccessUnlessGranted('CARGO_PIPELINE_PLACES_OF_EVENT_LIST');
        $cargoPipelinePlacesOfEvent = $this->entityManager
            ->getRepository(CargoPipelinePlacesOfEvent::class)
            ->findByParams($params);
        $pagination = new Pagination(
            $this->paginator->paginate(
                $cargoPipelinePlacesOfEvent,
                $params->getInt('page', 1),
                $params->getInt('limit', 10)
            )
        );

        return $this->prepareResponse($this->response, $pagination);
    }

    /**
     * Создание/обновление настроек типа перевозки.
     */
    public function createOrUpdateTransportationSettings(
        int $transportationId,
        ApiTransportationSettingsDto $apiTransportationSettingsDto
    ): ServiceResponse {
        $this->denyAccessUnlessGranted(AdditionalRouteVoter::TRANSPORTATION_SETTINGS_EDIT);

        $transportationSettings = null;
        /** @var Transportation $transportation */
        $transportation = $this->entityManager->getRepository(Transportation::class)->find($transportationId);

        if (!$transportation) {
            throw new NotFoundHttpException('Данный тип перевозки не существует');
        }

        $apiTransportationSettingsDto->setTransportation($transportation);

        $this->response->addErrors($this->validator->validate($apiTransportationSettingsDto));
        if (!$this->response->hasErrors()) {
            if ($transportation->getTransportationSettings()) {
                $transportationSettings = $this->updateTransportationSettings(
                    $apiTransportationSettingsDto,
                    $transportation->getTransportationSettings()
                );
            } else {
                $transportationSettings = new TransportationSettings();
                $transportationSettings = $this->createTransportationSettings(
                    $apiTransportationSettingsDto,
                    $transportationSettings
                );
            }
        }

        return $this->prepareResponse($this->response, $transportationSettings);
    }

    private function createTransportationSettings(
        ApiTransportationSettingsDto $apiTransportationSettingsDto,
        TransportationSettings $transportationSettings
    ) {
        if (!$this->response->hasErrors()) {
            $apiTransportationSettingsDto->createFieldSet($transportationSettings);
            $this->response->addErrors($this->validator->validate($transportationSettings));
            if (!$this->response->hasErrors()) {
                $this->entityManager->persist($transportationSettings);
                $this->entityManager->flush();
            }
        }

        return $transportationSettings;
    }

    private function updateTransportationSettings(
        ApiTransportationSettingsDto $apiTransportationSettingsDto,
        TransportationSettings $transportationSettings
    ) {
        if (!$this->response->hasErrors()) {
            $apiTransportationSettingsDto->updateFieldSet($transportationSettings);
            $this->response->addErrors($this->validator->validate($transportationSettings));
            if (!$this->response->hasErrors()) {
                $this->entityManager->flush();
            }
        }

        return $transportationSettings;
    }
}
