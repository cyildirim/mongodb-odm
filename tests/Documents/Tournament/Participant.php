<?php

declare(strict_types=1);

namespace Documents\Tournament;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorMap({"solo"=ParticipantSolo::class, "team"=ParticipantTeam::class})
 */
class Participant
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field
     *
     * @var string
     */
    private $name;

    /**
     * @ODM\ReferenceOne(targetDocument=Tournament::class, cascade={"all"})
     *
     * @var Tournament|null
     */
    protected $tournament;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setTournament(Tournament $tournament): void
    {
        $this->tournament = $tournament;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }
}
