<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Movie
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MovieRepository")
 */
class Movie {
    
    const RATING_RANGE = 5;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="apiID", type="integer")
     */
    protected $apiId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="releaseDate", type="date")
     */
    protected $releaseDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="openingRevenue", type="integer")
     */
    protected $openingRevenue;

    /**
     * @var integer
     *
     * @ORM\Column(name="criticRating", type="integer")
     */
    protected $criticRating;

    /**
     * @var integer
     *
     * @ORM\Column(name="audienceRating", type="integer")
     */
    protected $audienceRating;

    /**
     * @var string
     *
     * @ORM\Column(name="mpaaRating", type="string", length=7)
     */
    protected $mpaaRating;

    /**
     * @var ArrayCollection<Genre>
     * 
     * @ORM\ManyToMany(
     *      targetEntity="AppBundle\Entity\Genre",
     *      fetch="EAGER"
     * )
     */
    protected $genre;

    /**
     * @var ArrayCollection<Actor>
     * 
     * @ORM\ManyToMany(
     *      targetEntity="AppBundle\Entity\Actor",
     *      fetch="EAGER"
     * )
     */
    protected $cast;
    
    /**
     * @var ArrayCollection<Director>
     * 
     * @ORM\ManyToMany(
     *      targetEntity="AppBundle\Entity\Director",
     *      fetch="EAGER"
     * )
     */
    protected $directors;
    
    /**
     * @var Studio
     * 
     * @ORM\ManyToOne(
     *      targetEntity="AppBundle\Entity\Studio",
     *      inversedBy="movies",
     *      fetch="EAGER"
     * )
     */
    protected $studio;
    
    private $estimate;
    private $similarity;
    private $similarMovies;

    /**
     * Constructor
     */
    public function __construct() {
        $this->genre = new ArrayCollection();
        $this->cast = new ArrayCollection();
        $this->directors = new ArrayCollection();
    }
    
    /**
     * toString
     * 
     * @return string
     */
    public function __toString() {
        return $this->title . " (" . $this->releaseDate->format('Y')
                . ")\t\t\t{ID: " . $this->getApiId()
                . "\tRating: " . $this->mpaaRating
                . "\tRevenue: $" . number_format($this->openingRevenue)
                . "\tCritic Rating: " . $this->criticRating
                . "\tAudience Rating: " . $this->audienceRating
                . "}\n";
    }
    
    /**
     * Display the movie's estimate & similar movies
     * 
     * @return string
     */
    public function display() {
        $string = $this->__toString();
        
        $string .= "\t\tEstimate: $" . number_format($this->estimate) . "\n\n"
                . "\t\tSimilar Movies\n"
                . "\t\t==============\n";
        
        foreach ($this->similarMovies as $m) {
            $string .= "\t\t" . $m->getTitle() ." (" . $m->getReleaseDate()->format('Y')
                    . ")\t\t{Similarity: " . $m->getSimilarity()
                    . "\tRevenue: $" . number_format($m->getOpeningRevenue()) . "}\n";
        }
        
        $string .= "\n\n";
        
        return $string;
    }
    
    /**
     * Get the API title for queries
     * 
     * @return string 
     */
    public function getApiTitle() {
        return str_replace(" ", "+", $this->title);
    }
    
    /**
     * Calculate the similarity of two movies.
     * 
     * @param Movie $movie
     * @return integer Similarity value
     */
    public function similarity($movie) {
        $similarity = 0;
        
        // Check the type of the parameter
        if ($movie instanceof Movie) {
            // Make sure the release date is set for both objects
            if ($this->releaseDate != null && $movie->getReleaseDate() != null) {
                // Check if the month the movies are released is the same
                if ($this->releaseDate->format('m') == $movie->getReleaseDate()->format('m')) {
                    $similarity++;
                }
                
                // Check if the year the movies are released is the same
                if ($this->releaseDate->format('Y') == $movie->getReleaseDate()->format('Y')) {
                    $similarity++;
                }
            }
            
            // Make sure the MPAA rating is set for both objects
            if ($this->mpaaRating != null && $movie->getMpaaRating() != null) {
                // Check if the MPAA ratings are similar
                if ($this->mpaaRating == $movie->getMpaaRating()) {
                    $similarity++;
                }
            }
            
            // Make sure the critic rating is set for both objects
            if ($this->criticRating != null && $movie->getCriticRating() != null) {
                // Make sure the ratings are above 0
                if ($this->criticRating > 0 && $movie->getCriticRating() > 0) {
                    // Check to see if the ratings fall within a range
                    if (abs($this->criticRating - $movie->getCriticRating()) <= self::RATING_RANGE) {
                        $similarity++;
                    }
                }
            }
            
            // Make sure the audience rating is set for both objects
            if ($this->audienceRating != null && $movie->getAudienceRating() != null) {
                // Make sure the ratings are above 0
                if ($this->audienceRating > 0 && $movie->getAudienceRating() > 0) {
                    // Check to see if the ratings fall within a range
                    if (abs($this->audienceRating - $movie->getAudienceRating()) <= self::RATING_RANGE) {
                        $similarity++;
                    }
                }
            }
            
            // Make sure the studio is set for both objects
            if ($this->studio != null && $movie->getStudio() != null) {
                // Check to see if the two Studios are similar
                if ($this->studio->getId() == $movie->getStudio()->getId()) {
                    $similarity++;
                }
            }
            
            // Make sure the genre is set for both objects
            if (!$this->genre->isEmpty() && !$movie->getGenre()->isEmpty()) {
                // Check if there are matching genre
                foreach ($this->genre as $genre) {
                    foreach ($movie->getGenre() as $genre2) {
                        if ($genre->getId() == $genre2->getId()) {
                            $similarity++;
                        }
                    }
                }
            }
            
            // Make sure the cast is set for both objects
            if (!$this->cast->isEmpty() && !$movie->getCast()->isEmpty()) {
                // Check if there are matching cast
                foreach ($this->cast as $cast) {
                    foreach ($movie->getCast() as $cast2) {
                        if ($cast->getId() == $cast2->getId()) {
                            $similarity++;
                        }
                    }
                }
            }
            
            // Make sure the director is set for both objects
            if (!$this->directors->isEmpty() && !$movie->getDirectors()->isEmpty()) {
                // Check if there are matching directors
                foreach ($this->directors as $directors) {
                    foreach ($movie->getDirectors() as $directors2) {
                        if ($directors->getId() == $directors2->getId()) {
                            $similarity++;
                        }
                    }
                }
            }
            
            // Assign the similarity temporarily (Data not persisted)
            $this->setSimilarity($similarity);
            
            return $similarity;
        } else {
            // Invalid parameter type
            return "-1";
        }
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
     * Get API id
     * 
     * @return integer
     */
    public function getApiId() {
        return $this->apiId;
    }

    /**
     * Set API id
     * 
     * @param integer $apiId
     * @return Movie
     */
    public function setApiId($apiId) {
        $this->apiId = $apiId;
        
        return $this;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Movie
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set releaseDate
     *
     * @param \DateTime $releaseDate
     * @return Movie
     */
    public function setReleaseDate($releaseDate) {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    /**
     * Get releaseDate
     *
     * @return \DateTime 
     */
    public function getReleaseDate() {
        return $this->releaseDate;
    }

    /**
     * Set openingRevenue
     *
     * @param integer $openingRevenue
     * @return Movie
     */
    public function setOpeningRevenue($openingRevenue) {
        $this->openingRevenue = $openingRevenue;

        return $this;
    }

    /**
     * Get openingRevenue
     *
     * @return integer 
     */
    public function getOpeningRevenue() {
        return $this->openingRevenue;
    }

    /**
     * Set criticRating
     *
     * @param integer $criticRating
     * @return Movie
     */
    public function setCriticRating($criticRating) {
        $this->criticRating = $criticRating;

        return $this;
    }

    /**
     * Get criticRating
     *
     * @return integer 
     */
    public function getCriticRating() {
        return $this->criticRating;
    }

    /**
     * Set audienceRating
     *
     * @param integer $audienceRating
     * @return Movie
     */
    public function setAudienceRating($audienceRating) {
        $this->audienceRating = $audienceRating;

        return $this;
    }

    /**
     * Get audienceRating
     *
     * @return integer 
     */
    public function getAudienceRating() {
        return $this->audienceRating;
    }

    /**
     * Set mpaaRating
     *
     * @param string $mpaaRating
     * @return Movie
     */
    public function setMpaaRating($mpaaRating) {
        $this->mpaaRating = $mpaaRating;

        return $this;
    }

    /**
     * Get mpaaRating
     *
     * @return string 
     */
    public function getMpaaRating() {
        return $this->mpaaRating;
    }

    /**
     * Set genre
     *
     * @param ArrayCollection<Genre> $genre
     * @return Movie
     */
    public function setGenre($genre) {
        $this->genre = $genre;

        return $this;
    }

    /**
     * Get genre
     *
     * @return ArrayCollection<Genre>
     */
    public function getGenre() {
        return $this->genre;
    }

    /**
     * Set cast
     *
     * @param ArrayCollection<Actor> $cast
     * @return Movie
     */
    public function setCast($cast) {
        $this->cast = $cast;

        return $this;
    }

    /**
     * Get cast
     *
     * @return ArrayCollection<Actor>
     */
    public function getCast() {
        return $this->cast;
    }
    
    /**
     * Get directors
     * 
     * @return ArrayCollection<Director>
     */
    public function getDirectors() {
        return $this->directors;
    }

    /**
     * Set directors
     * 
     * @param ArrayCollection<Director> $directors
     * @return Movie
     */
    public function setDirectors($directors) {
        $this->directors = $directors;
        
        return $this;
    }

    /**
     * Get studio
     * 
     * @return Studio
     */
    public function getStudio() {
        return $this->studio;
    }

    /**
     * Set studio
     * 
     * @param Studio $studio
     * @return Movie
     */
    public function setStudio(Studio $studio) {
        $this->studio = $studio;
        
        return $this;
    }

    /**
     * Get estimate
     * 
     * @return integer
     */
    public function getEstimate() {
        return $this->estimate;
    }

    /**
     * Set estimate
     * 
     * @param integer $estimate
     * @return Movie
     */
    public function setEstimate($estimate) {
        $this->estimate = $estimate;
        
        return $this;
    }

    /**
     * Get similarity
     * 
     * @return decimal
     */
    public function getSimilarity() {
        return $this->similarity;
    }

    /**
     * Set similarity
     * 
     * @param decimal $similarity
     * @return Movie
     */
    public function setSimilarity($similarity) {
        $this->similarity = $similarity;
        
        return $this;
    }
    
    /**
     * Get similar movies
     * 
     * @return Movies[]
     */
    public function getSimilarMovies() {
        return $this->similarMovies;
    }

    /**
     * Set similar movies
     * 
     * @param Movies[] $similarMovies
     * @return Movie
     */
    public function setSimilarMovies($similarMovies) {
        $this->similarMovies = $similarMovies;
        
        return $this;
    }

}
