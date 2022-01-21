<?php

namespace App\Entity\Route;

use App\Entity\Contractor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteDisclaimerRepository")
 */
class RouteDisclaimer
{
    const STATUS_NEW = 0;
    const STATUS_APPROVED = 1;
    const STATUS_CANCELED = 2;

    const TYPE_SIMPLE = 0;
    const TYPE_AUCTION = 1;
    const TYPE_TENDER = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"Default"})
     */
    private $comment;

    /**
     * @ORM\Column(type="smallint", options={"default" : 0})
     * @Groups({"Default"})
     */
    private $status = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Contractor")
     * @ORM\JoinColumn(nullable=false)
     * @JMS\Groups({"RouteDisclaimer_info"})
     * @Groups({"RouteDisclaimer_info"})
     */
    private $contractor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Route", inversedBy="routeDisclaimers")
     * @ORM\JoinColumn(nullable=false)
     * @JMS\Groups({"RouteDisclaimer_info"})
     * @Groups({"RouteDisclaimer_info"})
     */
    private $route;

    /**
     * @ORM\Column(type="smallint", options={"default" : 0, "unsigned": true})
     * @Groups({"Default"})
     */
    private $type = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getContractor(): ?Contractor
    {
        return $this->contractor;
    }

    public function setContractor(?Contractor $contractor): self
    {
        $this->contractor = $contractor;

        return $this;
    }

    public function getRoute(): ?Route
    {
        return $this->route;
    }

    public function setRoute(?Route $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }
}
