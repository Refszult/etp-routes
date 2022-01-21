<?php

namespace App\Entity\Route;

use App\Entity\Tender\RouteTemplate;
use App\Entity\Tender\Tender;
use App\Entity\CustomerUser;
use App\Entity\Traits\GuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteOwnerRepository")
 */
class RouteOwner
{
    use GuidTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Groups({"Default"})
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"Default"})
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Tender\Tender", mappedBy="tenderOwners")
     * @JMS\Exclude()
     */
    private $tenders;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\CustomerUser", mappedBy="routeOwners")
     * @JMS\Exclude()
     */
    private $customerUsers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Tender\RouteTemplate", mappedBy="routeOwner")
     * @JMS\Exclude()
     */
    private $routeTemplates;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CustomerUser", inversedBy="linkedRouteOwners")
     * @Groups({"Default"})
     */
    private $agent;

    public function __construct()
    {
        $this->tenders = new ArrayCollection();
        $this->customerUsers = new ArrayCollection();
        $this->routeTemplates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Tender[]
     */
    public function getTenders(): Collection
    {
        return $this->tenders;
    }

    public function addTender(Tender $tender): self
    {
        if (!$this->tenders->contains($tender)) {
            $this->tenders[] = $tender;
            $tender->addTenderOwner($this);
        }

        return $this;
    }

    public function removeTender(Tender $tender): self
    {
        if ($this->tenders->contains($tender)) {
            $this->tenders->removeElement($tender);
            $tender->removeTenderOwner($this);
        }

        return $this;
    }

    /**
     * @return Collection|CustomerUser[]
     */
    public function getCustomerUsers(): Collection
    {
        return $this->customerUsers;
    }

    public function addCustomerUser(CustomerUser $customerUser): self
    {
        if (!$this->customerUsers->contains($customerUser)) {
            $this->customerUsers[] = $customerUser;
            $customerUser->addRouteOwner($this);
        }

        return $this;
    }

    public function removeCustomerUser(CustomerUser $customerUser): self
    {
        if ($this->customerUsers->contains($customerUser)) {
            $this->customerUsers->removeElement($customerUser);
            $customerUser->removeRouteOwner($this);
        }

        return $this;
    }

    /**
     * @return Collection|RouteTemplate[]
     */
    public function getRouteTemplates(): Collection
    {
        return $this->routeTemplates;
    }

    public function addRouteTemplate(RouteTemplate $routeTemplate): self
    {
        if (!$this->routeTemplates->contains($routeTemplate)) {
            $this->routeTemplates[] = $routeTemplate;
            $routeTemplate->setRouteOwner($this);
        }

        return $this;
    }

    public function removeRouteTemplate(RouteTemplate $routeTemplate): self
    {
        if ($this->routeTemplates->contains($routeTemplate)) {
            $this->routeTemplates->removeElement($routeTemplate);
            // set the owning side to null (unless already changed)
            if ($routeTemplate->getRouteOwner() === $this) {
                $routeTemplate->setRouteOwner(null);
            }
        }

        return $this;
    }

    public function getAgent(): ?CustomerUser
    {
        return $this->agent;
    }

    public function setAgent(?CustomerUser $agent): self
    {
        $this->agent = $agent;

        return $this;
    }
}
