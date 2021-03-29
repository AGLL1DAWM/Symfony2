<?php

namespace App\Entity;

use App\Repository\ControllerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ControllerRepository::class)
 */
class Controller
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=34)
     */
    private $Usuer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuer(): ?string
    {
        return $this->Usuer;
    }

    public function setUsuer(string $Usuer): self
    {
        $this->Usuer = $Usuer;

        return $this;
    }
}
