<?php

namespace AppBundle\Command;

use AppBundle\Entity\Actor;
use AppBundle\Entity\Genre;
use AppBundle\Entity\Director;
use AppBundle\Entity\Movie;
use AppBundle\Entity\Studio;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 
 */
class UpcomingMovieCommand extends ContainerAwareCommand {

    const K_NUM = 11;

    public function configure() {
        $this->setName('mrp:upcoming:predict')
                ->setDescription('Provides estimates for opening weekend revenue');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        $movieRepo = $em->getRepository('AppBundle:Movie');
        $genreRepo = $em->getRepository('AppBundle:Genre');
        $actorRepo = $em->getRepository('AppBundle:Actor');
        $directorRepo = $em->getRepository('AppBundle:Director');
        $studioRepo = $em->getRepository('AppBundle:Studio');
        
//        $apiKey = $this->container->getParameter('api_key');
        $apiKey = "55c4wwmmfea8n9sg2faqqjd6";
        
        $url = "http://api.rottentomatoes.com/api/public/v1.0/lists/movies/upcoming.json?page_limit=16&page=1&country=us&apikey=" . $apiKey;
        
        $upcomingMovies = array();
        
        $movies = $movieRepo->findAll();
        
        $data = json_decode(file_get_contents($url), true);
        
        if ($data['total'] > 0) {
            for ($i = 0; $i < $data['total']; ++$i) {
                if (array_key_exists('id', $data['movies'][$i])) {
                    $movie = new Movie();
                    
                    $id = $data['movies'][$i]['id'];
                    $url2 = "http://api.rottentomatoes.com/api/public/v1.0/movies/"
                                . $id . ".json?apikey=" . $apiKey;
                    
                    $jsonData = json_decode(file_get_contents($url2), true);

                    $movie->setApiId($id);
                    
                    if (array_key_exists('title', $jsonData)) {
                        $movie->setTitle($jsonData['title']);
                    }
                    
                    if (array_key_exists('release_dates', $jsonData)
                            && array_key_exists('theater', $jsonData['release_dates'])) {
                        $movie->setReleaseDate(new DateTime($jsonData['release_dates']['theater']));
                    }

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
                    
                    $similarMovies = array();
                    $revenue = 0;

                    foreach ($movies as $m) {
                        $m->similarity($movie);
                    }

                    usort($movies, function ($a, $b) {
                            return $b->getSimilarity() - $a->getSimilarity();
                    });

                    for ($j = 0; $j < self::K_NUM; ++$j) {
                        $similarMovies[] = $movies[$j];
                        $revenue += $movies[$j]->getOpeningRevenue();
                    }
                    
                    $movie->setSimilarMovies($similarMovies)
                            ->setEstimate(round($revenue / self::K_NUM));
                    
                    // Add the upcoming movie to the array
                    $upcomingMovies[] = $movie;
                    
                    $output->writeln("<info>" . $movie->display() . "</info>");
                } else {
                    $output->writeln("<error>ERROR: COULD NOT FIND ID: " . $movie->getApiTitle() . "</error>");
                }
            }
        } else {
            $output->writeln("<error>ERROR: NO DATA FOUND</error>");
        }
    }

}