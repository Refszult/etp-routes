<?php

namespace App\Service\Route;

use App\Dto\Tender\ApiRouteTemplateDto;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;

class RouteTemplateDeserializer
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
     * Десериализация в json в DTO.
     *
     * @param string $data - данные в формате JSON
     *
     * @return ApiRouteTemplateDto|null
     */
    public function deserializeToApiDto(string $data): ?ApiRouteTemplateDto
    {
        return $this->serializer->deserialize($data, ApiRouteTemplateDto::class, 'json', $this->context);
    }
}
