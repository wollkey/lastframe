<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\DomCrawler\Crawler;

final readonly class HtmlParser
{
    public function getFilmList(string $html): array
    {
        $crawler = new Crawler($html);

        $ownerUsername = 'List owner';
        $bodyNode = $crawler->filter('body.list-page')->first();
        if ($bodyNode->count() > 0) {
            $ownerUsername = $bodyNode->attr('data-owner') ?? '';
        }

        return $crawler
            ->filter('ul.js-list-entries li')
            ->each(function (Crawler $li) use ($ownerUsername) {
                $reactComponent = $li->filter('div.react-component')->first();

                $filmId = $reactComponent->count() > 0
                    ? ($reactComponent->attr('data-film-id') ?? '')
                    : '';

                $slug = $reactComponent->count() > 0
                    ? ($reactComponent->attr('data-item-slug') ?? '')
                    : '';

                $title = '';
                $imgNode = $li->filter('img');
                if ($imgNode->count() > 0) {
                    $title = $imgNode->attr('alt') ?? '';
                }

                if (empty($title) && $reactComponent->count() > 0) {
                    $itemName = $reactComponent->attr('data-item-name') ?? '';
                    $title = preg_replace('/\s+\(\d{4}\)$/', '', $itemName);
                }

                $ownerRating = $li->attr('data-owner-rating') ?? '';

                return [
                    'filmId' => $filmId,
                    'slug' => $slug,
                    'title' => $title,
                    'userRatings' => [
                        [
                            'user' => $ownerUsername,
                            'rating' => $ownerRating,
                            'isOwner' => true,
                        ],
                    ],
                ];
            });
    }

    public function getFriendsRatings(string $html): array
    {
        $crawler = new Crawler($html);
        $ratings = [];

        $crawler->filter('tbody tr')->each(function (Crawler $tr) use (&$ratings) {
            $usernameNode = $tr->filter('.table-person .name');
            $ratingNode = $tr->filter('td span.rating');

            if ($usernameNode->count() === 0) {
                return;
            }

            $username = trim($usernameNode->text());

            if ($ratingNode->count() > 0) {
                $ratingClass = $ratingNode->attr('class') ?? '';

                if (preg_match('/rated-(\d+)/', $ratingClass, $matches)) {
                    $ratings[] = [
                        'user' => $username,
                        'rating' => $matches[1],
                        'isOwner' => false,
                    ];
                }
            }
        });

        return $ratings;
    }
}