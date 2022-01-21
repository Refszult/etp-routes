<?php

namespace App\Service\Route;

use App\Dto\Route\ApiContractorRouteDto;
use App\Dto\Route\ApiCustomerRouteDto;
use App\Dto\Route\MQRouteDto;
use App\Model\Route\CustomerRouteModel;
use App\Model\Route\RouteModel;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;

class RouteDeserializer
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var DeserializationContext
     */
    protected $context;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->context = new DeserializationContext();
        $this->context->setGroups(['Default', 'Route_des']);
        $this->context->enableMaxDepthChecks();
    }

    public function setGroups(array $groups)
    {
        $this->context = new DeserializationContext();
        $this->context->setGroups($groups);
        $this->context->enableMaxDepthChecks();
    }

    /**
     * Десериализация в json в модель.
     *
     * @param string $data - данные в формате JSON
     *
     * @return RouteModel|null
     */
    public function deserializeToModel(string $data): ?RouteModel
    {
        return $this->serializer->deserialize($data, RouteModel::class, 'json', $this->context);
    }

    /**
     * @param string $data
     * @return CustomerRouteModel|null
     */
    public function deserializeToCustomerDto(string $data): ?ApiCustomerRouteDto
    {
        return $this->serializer->deserialize($data, ApiCustomerRouteDto::class, 'json', $this->context);
    }

    /**
     * @param string $data
     * @return MQRouteDto|null
     */
    public function deserializeToMQDto(string $data): ?MQRouteDto
    {
        return $this->serializer->deserialize($data, MQRouteDto::class, 'json', $this->context);
    }

    /**
     * @param string $data
     * @return ApiContractorRouteDto|null
     */
    public function deserializeToApiContractorDto(string $data): ?ApiContractorRouteDto
    {
        return $this->serializer->deserialize($data, ApiContractorRouteDto::class, 'json', $this->context);
    }
}
