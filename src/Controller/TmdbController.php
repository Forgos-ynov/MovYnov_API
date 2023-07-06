<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TmdbController extends AbstractController
{
    private string $prefixeUrl;
    private string $prefixeUrlSearch;
    private string $sufixeUrl;

    public function __construct()
    {
        $this->prefixeUrl = "https://api.themoviedb.org/3/movie/";
        $this->prefixeUrlSearch = "https://api.themoviedb.org/3/search/";
        $this->sufixeUrl = "?append_to_response=credits,watch/providers,videos&language=fr-FR&api_key=b8e285eb83d6d2fccd9f0acc529b5004";
    }

    #[Route('/api/tmdb/movies/popular', name: 'get_tmdb_getPopularMovies', methods: "GET")]
    public function getPopularMovies(): JsonResponse
    {
        $url = $this->prefixeUrl . "popular" . $this->sufixeUrl;
        $json = $this->getJsonFromUrl($url);
        return $this->json($json);
    }

    #[Route('/api/tmdb/movies/recommandation/{idMovieRecommandation}', name: 'get_tmdb_getRecommandedMovies', methods: "GET")]
    public function getRecommandedMovies(int $idMovieRecommandation): JsonResponse
    {
        $url = $this->prefixeUrl . $idMovieRecommandation . "/recommendations" . $this->sufixeUrl;
        $json = $this->getJsonFromUrl($url);
        return $this->json($json);
    }

    #[Route('/api/tmdb/movies/{idMovie}', name: 'get_tmdb_getInformationsOfOneMovie', methods: "GET")]
    public function getInformationsOfOneMovie(int $idMovie): JsonResponse
    {
        $url = $this->prefixeUrl . $idMovie . $this->sufixeUrl;
        $json = $this->getJsonFromUrl($url);
        return $this->json($json);
    }

    #[Route('/api/tmdb/movies/searchName/{searchName}', name: 'get_tmdb_getMoviesByName', methods: "GET")]
    public function getMoviesByName(string $searchName): JsonResponse
    {
        $url = $this->prefixeUrlSearch . "movie" . $this->sufixeUrl . "&query=" . $searchName;
        var_dump($url); die();
        $json = $this->getJsonFromUrl($url);
        return $this->json($json);
    }

    private function getJsonFromUrl(string $url)
    {
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);

        $response = curl_exec($curl);
        return json_decode($response);
    }
}
