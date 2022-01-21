<?php


namespace App\Service\Route;


use App\Dto\Route\ApiTransportationSettingsDto;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;

class TransportationSettingsDeserializer
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
     *
     * @return ApiTransportationSettingsDto|null
     */
    public function deserializeToApiDto(string $data): ?ApiTransportationSettingsDto
    {
        return $this->serializer->deserialize($data, ApiTransportationSettingsDto::class, 'json', $this->context);
    }
}
