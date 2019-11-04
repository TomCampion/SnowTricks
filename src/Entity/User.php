<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(
 *     "email",
 *     message="cet email est déjà utilisé"
 * )
 * @UniqueEntity(
 *     "username",
 *     message="ce nom d'utilisateur est déjà utilisé"
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class User implements UserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     *  @Assert\NotNull(
     *     message= "Vous devez renseigner une adresse email"
     *  )
     *  @Assert\NotBlank(
     *     message= "Vous devez renseigner une adresse email"
     *  )
     * @Assert\Length(
     *      max = 180,
     *      maxMessage = "L'email ne peut pas excéder {{ limit }} caractères"
     * )
     * @Assert\Email(
     *     message = "L'email '{{ value }}' n'est pas valide.",
     *     checkMX = true
     * )
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="author")
     */
    private $comments;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $ban = 0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $profilePictureFileName;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDate;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Trick", mappedBy="author")
     */
    private $tricks;

    /**
     * @ORM\Column(type="string", length=55, unique=true)
     * @Assert\NotNull(
     *     message= "Vous devez renseigner un nom d'utilisateur"
     * )
     * @Assert\NotBlank(
     *     message= "Vous devez renseigner un nom d'utilisateur"
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 55,
     *      minMessage = "Votre nom d'utilisateur doit faire au moins {{ limit }} caractères",
     *      maxMessage = "Votre nom d'utilisateur ne doit pas excéder {{ limit }} caractères"
     * )
     */
    private $username;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isActive = 0;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\token", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $confirmationToken;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\token", cascade={"persist", "remove"})
     */
    private $passwordToken;

    /**
     * * @Assert\NotNull(
     *     message= "Vous devez renseigner un mot de passe"
     * )
     * @Assert\NotBlank(
     *     message= "Vous devez renseigner un mot de passe"
     * )
     * @Assert\Length(
     *      min = 4,
     *      max = 50,
     *      minMessage = "Votre mot de passe doit faire au moins {{ limit }} caractères",
     *      maxMessage = "Votre mot de passe ne doit pas excéder {{ limit }} caractères"
     * )
     */
    private $plainPassword;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->tricks = new ArrayCollection();

        $this->roles = array('ROLE_USER');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setAuthor($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor(null);
            }
        }

        return $this;
    }

    public function getBan(): ?bool
    {
        return $this->ban;
    }

    public function setBan(bool $ban): self
    {
        $this->ban = $ban;

        return $this;
    }

    public function getprofilePictureFileName(): ?string
    {
        return $this->profilePictureFileName;
    }

    public function setprofilePictureFileName(?string $profilePictureFileName): self
    {
        $this->profilePictureFileName = $profilePictureFileName;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return Collection|Trick[]
     */
    public function getTricks(): Collection
    {
        return $this->tricks;
    }

    public function addTrick(Trick $trick): self
    {
        if (!$this->tricks->contains($trick)) {
            $this->tricks[] = $trick;
            $trick->setAuthor($this);
        }

        return $this;
    }

    public function removeTrick(Trick $trick): self
    {
        if ($this->tricks->contains($trick)) {
            $this->tricks->removeElement($trick);
            // set the owning side to null (unless already changed)
            if ($trick->getAuthor() === $this) {
                $trick->setAuthor(null);
            }
        }

        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getConfirmationToken(): ?token
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(token $confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function getPasswordToken(): ?token
    {
        return $this->passwordToken;
    }

    public function setPasswordToken(?token $passwordToken): self
    {
        $this->passwordToken = $passwordToken;

        return $this;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function creationDate()
    {
        $this->setCreationDate(new \Datetime());
    }
}
