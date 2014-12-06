<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Actor;
use AppBundle\Entity\Director;
use AppBundle\Entity\Genre;
use AppBundle\Entity\Movie;
use AppBundle\Entity\Studio;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {

    /**
     * @Route("/", name="homepage")
     * @Method("GET")
     * @Template()
     */
    public function indexAction() {
        $fh = fopen('../app/csvDumps/movies2.csv', 'r');
        
        self::extractData($fh);
        
        return array(
            
        );
    }
    
    /**
     * Retrieve the movie data from the Rotten Tomatoes API.
     * 
     * @param type $fh File Pointer
     */
    private function extractData($fh) {
        $em = $this->container->get('doctrine')->getManager();
        
        $genreRepo = $em->getRepository('AppBundle:Genre');
        $actorRepo = $em->getRepository('AppBundle:Actor');
        $directorRepo = $em->getRepository('AppBundle:Director');
        $studioRepo = $em->getRepository('AppBundle:Studio');
        
//        $apiKey = $this->container->getParameter('api_key');
        $apiKey = "55c4wwmmfea8n9sg2faqqjd6";
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

                                $em->persist($genre);
                                $em->flush();
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

                                $em->persist($actor);
                                $em->flush();
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

                                $em->persist($director);
                                $em->flush();
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

                            $em->persist($studio);
                            $em->flush();
                        }

                        $movie->setStudio($studio);
                    }

                    echo $movie;

                    $em->persist($movie);
                    $em->flush();
                } else {
                    echo "COULD NOT FIND ID: " . $movie->getApiTitle() . "\n";
                }
            } else {
                echo "COULD NOT FIND: " . $movie->getApiTitle() . "\n";
            }
        }
    }

}
