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
        $em = $this->container->get('doctrine')->getManager();
        
        $genreRepo = $em->getRepository('AppBundle:Genre');
        $actorRepo = $em->getRepository('AppBundle:Actor');
        $directorRepo = $em->getRepository('AppBundle:Director');
        $studioRepo = $em->getRepository('AppBundle:Studio');
        
        $apiKey = $this->container->getParameter('api_key');
        $pageLimit = "&page_limit=1";
        $url = "http://api.rottentomatoes.com/api/public/v1.0/movies.json?apikey="
                . $apiKey . "&q=";
        
        while (($csvData = fgetcsv($fh)) !== false) {
            $movie = new Movie();
            
            $movie->setTitle($csvData[0])
                    ->setOpeningRevenue($csvData[1])
                    ->setReleaseDate(new DateTime($csvData[4]));
            
            $data = json_decode(file_get_contents($url . $movie->getApiTitle() . $pageLimit), true);
            
            if ($data['total'] > 0) {
                if (array_key_exists('id', $data['movies'][0])) {
                    $id = $data['movies'][0]['id'];

                    $url2 = "http://api.rottentomatoes.com/api/public/v1.0/movies/"
                            . $id . ".json?apikey=" . $apiKey;

                    $jsonData = json_decode(file_get_contents($url2), true);
                    
                    $movie->setApiId($id);

                    if (array_key_exists('mpaa_rating', $jsonData)) {
                        $movie->setMpaaRating($jsonData['mpaa_rating']);
                    }
                    
                    if (array_key_exists('ratings', $jsonData)) {
                        if (array_key_exists('critics_score', $jsonData['ratings'])) {
                            $movie->setCriticRating($jsonData['ratings']['critics_score']);
                        }
                        
                        if (array_key_exists('audience_score', $jsonData['ratings'])) {
                            $movie->setAudienceRating($jsonData['ratings']['audience_score']);
                        }
                    }

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
