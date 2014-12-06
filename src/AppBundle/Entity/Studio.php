<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Studio
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Studio {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=false)
     */
    private $name;

    /**
     * @var ArrayCollection<Movie>
     * 
     * @ORM\OneToMany(
     *      targetEntity="AppBundle\Entity\Movie",
     *      mappedBy="studio",
     *      cascade={"ALL"},
     *      orphanRemoval=true,
     *      fetch="LAZY"
     * )
     */
    private $movies;

    /**
     * Constructor
     * 
     * @param string $name
     */
    public function __construct($name) {
        $this->movies = new ArrayCollection();
        $this->name = $name;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Studio
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Get movies
     * 
     * @return ArrayCollection<Movie>
     */
    public function getMovies() {
        return $this->movies;
    }

    /**
     * Set movies
     * 
     * @param ArrayCollection<Movie> $movies
     * @return Studio
     */
    public function setMovies($movies) {
        $this->movies = $movies;
        
        return $this;
    }
    
}
