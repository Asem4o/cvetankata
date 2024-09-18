<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'text')]
    private $text;

    #[ORM\Column(type: 'boolean')]
    private $multipleCorrect;

    #[ORM\ManyToOne(targetEntity: Test::class, inversedBy: 'questions')]
    private $test;

    #[ORM\OneToMany(mappedBy: 'question', targetEntity: Answer::class, cascade: ['persist', 'remove'])]
    private $answers;

    // Add the relationship with Discipline
    #[ORM\ManyToOne(targetEntity: Discipline::class)]
    #[ORM\JoinColumn(nullable: false)]  // Ensures discipline cannot be null
    private $discipline;

    #[ORM\Column(type: 'uuid', unique: true)]
    private $guid;

    public function __construct()
    {
        // Automatically generate a new UUID when the entity is created
        $this->guid = Uuid::v4();
    }

    public function getGuid(): ?Uuid
    {
        return $this->guid;
    }

    // Set the UUID (accepts Uuid object)
    public function setGuid(Uuid $guid): self
    {
        $this->guid = $guid;
        return $this;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getMultipleCorrect(): ?bool
    {
        return $this->multipleCorrect;
    }

    public function setMultipleCorrect(bool $multipleCorrect): self
    {
        $this->multipleCorrect = $multipleCorrect;
        return $this;
    }

    public function getTest(): ?Test
    {
        return $this->test;
    }

    public function setTest(?Test $test): self
    {
        $this->test = $test;
        return $this;
    }

    public function getAnswers()
    {
        return $this->answers;
    }

    public function setAnswers($answers): void
    {
        $this->answers = $answers;
    }

    public function getDiscipline(): ?Discipline
    {
        return $this->discipline;
    }

    public function setDiscipline(?Discipline $discipline): self
    {
        $this->discipline = $discipline;
        return $this;
    }
}
