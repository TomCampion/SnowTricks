<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VideoRepository")
 */
class Video
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotNull(
     *     message= "Vous devez renseigner ce champ"
     *  )
     *  @Assert\NotBlank(
     *     message= "Vous devez renseigner ce champ"
     *  )
     * @ORM\Column(type="string", length=511)
     */
    private $iframe;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Trick", inversedBy="videos")
     * @ORM\JoinColumn(nullable=false)
     */
    private $trick;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIframe(): ?string
    {
        return $this->iframe;
    }

    public function setIframe(string $iframe): self
    {
        $this->iframe = $iframe;

        return $this;
    }

    public function getTrick(): ?trick
    {
        return $this->trick;
    }

    public function setTrick(?trick $trick): self
    {
        $this->trick = $trick;

        return $this;
    }
}
