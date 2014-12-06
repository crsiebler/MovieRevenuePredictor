<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Actor;
use AppBundle\Entity\Director;
use AppBundle\Entity\Genre;
use AppBundle\Entity\Movie;
use AppBundle\Entity\Studio;
use DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * 
 */
class MovieFixtures extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface {

    /**
     * 
     * @return int
     */
    public function getOrder() {
        return 110;
    }

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    /**
     * 
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(\Doctrine\Common\Persistence\ObjectManager $manager) {
        $fh = fopen('app/csvDumps/movies.csv', 'r');
//        $fh1 = fopen('app/csvDumps/movies1.csv', 'r');
//        $fh2 = fopen('app/csvDumps/movies2.csv', 'r');
        
        self::extractData($fh, $manager);
//        self::extractData($fh1, $manager);
//        self::extractData($fh2, $manager);

        echo "========================\n"
            . "COMPLETED LOADING MOVIES\n"
            . "========================\n";    
    }
    
    /**
     * Retrieve the movie data from the Rotten Tomatoes API.
     * 
     * @param type $fh File Pointer
     * @param ObjectManager $manager
     */
    private function extractData($fh, $manager) {
        // Grab the entity manager
        $em = $this->container->get('doctrine')->getManager();
        
        // Grab the repository for the entities
        $genreRepo = $em->getRepository('AppBundle:Genre');
        $actorRepo = $em->getRepository('AppBundle:Actor');
        $directorRepo = $em->getRepository('AppBundle:Director');
        $studioRepo = $em->getRepository('AppBundle:Studio');
        
        // Declare the API key and URL for RESTful service
        $apiKey = $this->container->getParameter('api_key');
        $pageLimit = "&page_limit=1";
        $url = "http://api.rottentomatoes.com/api/public/v1.0/movies.json?apikey="
                . $apiKey . "&q=";
        
        // Loop through the CSV data
        while (($csvData = fgetcsv($fh)) !== false) {
            // Initialize a new Movie
            $movie = new Movie();
            
            // Retrieve the CSV data for each movie
            $movie->setTitle($csvData[0])
                    ->setOpeningRevenue($csvData[1])
                    ->setReleaseDate(new DateTime($csvData[4]));
            
            // Search API for the movie title
            $data = json_decode(file_get_contents($url . $movie->getApiTitle() . $pageLimit), true);
            
            // Make sure the movie was found from the title search
            if ($data['total'] > 0) {
                // Make sure the id for the movie is given
                if (array_key_exists('id', $data['movies'][0])) {
                    // Grab the ID from the movie results
                    $id = $data['movies'][0]['id'];

                    // Specify the RESTful service to get detailed movie data
                    $url2 = "http://api.rottentomatoes.com/api/public/v1.0/movies/"
                            . $id . ".json?apikey=" . $apiKey;

                    // Get JSON data from API
                    $jsonData = json_decode(file_get_contents($url2), true);
                    
                    $movie->setApiId($id);

                    // Check for MPAA rating
                    if (array_key_exists('mpaa_rating', $jsonData)) {
                        $movie->setMpaaRating($jsonData['mpaa_rating']);
                    }
                    
                    // Check for ratings
                    if (array_key_exists('ratings', $jsonData)) {
                        if (array_key_exists('critics_score', $jsonData['ratings'])) {
                            $movie->setCriticRating($jsonData['ratings']['critics_score']);
                        }
                        
                        if (array_key_exists('audience_score', $jsonData['ratings'])) {
                            $movie->setAudienceRating($jsonData['ratings']['audience_score']);
                        }
                    }

                    // Check for genres
                    if (array_key_exists('genres', $jsonData)) {
                        foreach ($jsonData['genres'] as $genreName) {
                            $genre = $genreRepo->findOneByName($genreName);

                            if ($genre == null) {
                                $genre = new Genre($genreName);

                                $manager->persist($genre);
                                $manager->flush();
                            }

                            if (!$movie->getGenre()->contains($genre)) {
                                $movie->getGenre()->add($genre);
                            }
                        }
                    }

                    // Check for cast
                    if (array_key_exists('abridged_cast', $jsonData)) {
                        foreach ($jsonData['abridged_cast'] as $actorData) {
                            $actorName = $actorData['name'];

                            $actor = $actorRepo->findOneByName($actorName);

                            if ($actor == null) {
                                $actor = new Actor($actorName);

                                $manager->persist($actor);
                                $manager->flush();
                            }

                            if (!$movie->getCast()->contains($actor)) {
                                $movie->getCast()->add($actor);
                            }
                        }
                    }

                    // Check for directors
                    if (array_key_exists('abridged_directors', $jsonData)) {
                        foreach ($jsonData['abridged_directors'] as $directorData) {
                            $directorName = $directorData['name'];

                            $director = $directorRepo->findOneByName($directorName);

                            if ($director == null) {
                                $director = new Director($directorName);

                                $manager->persist($director);
                                $manager->flush();
                            }
                            
                            if (!$movie->getDirectors()->contains($director)) {
                                $movie->getDirectors()->add($director);
                            }
                        }
                    }

                    // Check for studio
                    if (array_key_exists('studio', $jsonData)) {
                        $studio = $studioRepo->findOneByName($jsonData['studio']);

                        if ($studio == null) {
                            $studio = new Studio($jsonData['studio']);

                            $manager->persist($studio);
                            $manager->flush();
                        }

                        $movie->setStudio($studio);
                    }

                    echo $movie;

                    // Persist the movie to the database
                    $manager->persist($movie);
                    $manager->flush();
                } else {
                    echo "COULD NOT FIND ID: " . $movie->getApiTitle() . "\n";
                }
            } else {
                echo "COULD NOT FIND: " . $movie->getApiTitle() . "\n";
            }
        }
    }

}
