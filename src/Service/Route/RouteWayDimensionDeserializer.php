<?php

namespace App\Service\Route;

use App\Dto\Route\ApiRouteWayDimensionDto;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;

class RouteWayDimensionDeserializer
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
        $this->context->setGroups(['Default']);
        $this->context->enableMaxDepthChecks();
    }

    public function setGroups(array $groups)
    {
        $this->context = new DeserializationContext();
        $this->context->setGroups($groups);
        $this->context->enableMaxDepthChecks();
    }

    /**
     * @param string $data
     * @return ApiRouteWayDimensionDto|null
     */
    public function deserializeToApiDto(string $data): ?ApiRouteWayDimensionDto
    {
        return $this->serializer->deserialize($data, ApiRouteWayDimensionDto::class, 'json', $this->context);
    }
}
