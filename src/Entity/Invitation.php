<?php

namespace App\Entity;

use App\Enum\InvitationStatus;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\InvitationRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

#[ORM\Entity(repositoryClass: InvitationRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            securityPostDenormalize: "object.getEvent().getCreatedBy() == user",
            securityPostDenormalizeMessage: "Seul le créateur de l’événement peut inviter."
        ),
        new Put(
            security: "object.getInvitee() == user",
            securityMessage: "Seul l’invité peut modifier via PUT (tous champs requis)."
        ),
        new Patch(
            security: "object.getInvitee() == user",
            securityMessage: "Seul l’invité peut accepter ou refuser via PATCH."
        ),
        new Delete(
            security: "object.getEvent().getCreatedBy() == user",
            securityMessage: "Seul le créateur de l’événement peut supprimer une invitation."
        ),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'invitee' => 'exact',
    'event'   => 'exact'
])]
class Invitation
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $invitee = null;

    #[ORM\Column(enumType: InvitationStatus::class)]
    private InvitationStatus $status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getInvitee(): ?User
    {
        return $this->invitee;
    }

    public function setInvitee(User $invitee): self
    {
        $this->invitee = $invitee;
        return $this;
    }

    public function getStatus(): InvitationStatus
    {
        return $this->status;
    }

    public function setStatus(InvitationStatus $status): self
    {
        $this->status = $status;
        return $this;
    }
}
