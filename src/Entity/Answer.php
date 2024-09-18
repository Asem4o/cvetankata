<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid; // Symfony's UUID component

#[ORM\Entity]
class Answer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'uuid', unique: true)]
    private $guid;

    /**
     * @param $guid
     */
    public function __construct()
    {
        $this->guid = Uuid::v4(); // Generate a unique UUID when an answer is created
    }



    #[ORM\Column(type: 'string', length: 255)]
    private $text;

    #[ORM\Column(type: 'boolean')]
    private $isCorrect; // The field that tells if the answer is correct

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'answers')]
    private $question;

    public function getGuid(): ?Uuid
    {
        return $this->guid;
    }

    public function setGuid(Uuid $guid): self
    {
        $this->guid = $guid;

        return $this;
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    // Getter for text
    public function getText(): ?string
    {
        return $this->text;
    }

    // Setter for text
    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    // Getter for isCorrect
    public function isCorrect(): ?bool
    {
        return $this->isCorrect;
    }

    // Setter for isCorrect
    public function setIsCorrect(bool $isCorrect): self
    {
        $this->isCorrect = $isCorrect;
        return $this;
    }

    // Getter and Setter for Question
    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;
        return $this;
    }
}
