<?php

declare(strict_types=1);

namespace App;

final readonly class RatingService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private HtmlParser $parser,
    ) {
    }

    public function getFriendRatings(string $username, string $filmSlug): array
    {
        $url = "$username/friends/film/$filmSlug/";
        $html = $this->httpClient->get($url);

        return $this->parser->getFriendsRatings($html);
    }

    public function getFilmList(string $userName, string $listName): array
    {
        $url = "$userName/list/$listName/";
        $html = $this->httpClient->get($url);

        return $this->parser->getFilmList($html);
    }

    public function getFilmListWithUserRatings(string $username, string $listName): array
    {
        $movieList = $this->getFilmList($username, $listName);

        foreach ($movieList as &$movie) {
            $filmSlug = $movie['slug'];

            $movie['userRatings'] = [
                ...$this->getFriendRatings($username, $filmSlug),
                ...$movie['userRatings'],
            ];
        }

        return $movieList;
    }
}