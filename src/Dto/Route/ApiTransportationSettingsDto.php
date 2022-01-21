<?php

namespace App\Dto\Route;

use App\Dto\DtoClass;
use App\Dto\DtoInterface;
use App\Entity\Route\Transportation;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class ApiTransportationSettingsDto extends DtoClass implements DtoInterface
{
    /**
     * @var int
     * @Groups({"Default"})
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var bool
     *
     * @Groups({"Default"})
     * @JMS\Type("boolean")
     */
    protected $firstBid;

    /**
     * @var int
     *
     * @Groups({"Default"})
     * @JMS\Type("integer")
     */
    protected $timeToCancel;

    /**
     * @var Transportation
     * @Groups({"Default"})
     * @JMS\Type("App\Entity\Route\Transportation")
     */
    protected $transportation;

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $createFields = [
        'firstBid',
        'timeToCancel',
        'transportation',
    ];

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $updateFields = [
        'firstBid',
        'timeToCancel',
        'transportation',
    ];

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): ApiTransportationSettingsDto
    {
        $this->id = $id;

        return $this;
    }

    public function isFirstBid(): bool
    {
        return $this->firstBid;
    }

    public function setFirstBid(bool $firstBid): ApiTransportationSettingsDto
    {
        $this->firstBid = $firstBid;

        return $this;
    }

    public function getTimeToCancel(): int
    {
        return $this->timeToCancel;
    }

    public function setTimeToCancel(int $timeToCancel): ApiTransportationSettingsDto
    {
        $this->timeToCancel = $timeToCancel;

        return $this;
    }

    public function getTransportation(): Transportation
    {
        return $this->transportation;
    }

    public function setTransportation(Transportation $transportation): ApiTransportationSettingsDto
    {
        $this->transportation = $transportation;

        return $this;
    }
}
