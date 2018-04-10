<?php
declare(strict_types=1);

namespace Mleko\LetsPlay\Repository;


use Mleko\LetsPlay\Entity\Match;
use Mleko\LetsPlay\Entity\MatchSet;
use Mleko\LetsPlay\ValueObject\MatchTeam;
use Mleko\LetsPlay\ValueObject\Uuid;

class MatchSetRepository
{
    /** @var JsonStorage */
    private $storage;

    /**
     * UserRepository constructor.
     * @param JsonStorage $storage
     */
    public function __construct(JsonStorage $storage) {
        $this->storage = $storage;
    }

    public function save(MatchSet $matchSet) {
        $sets = $this->getSets();
        $sets[$matchSet->getId()->getUuid()] = $matchSet;
        $this->saveMatchSets($sets);
    }

    /**
     * @return MatchSet[]
     */
    public function getSets(): array {
        $data = $this->storage->getData();
        return \array_map([$this, "denormalizeSet"], $data["matchSets"] ?? []);
    }

    public function getSet($setId): ?MatchSet {
        $data = $this->storage->getData();
        if (!isset($data["matchSets"][$setId])) {
            return null;
        }
        return $this->denormalizeSet($data["matchSets"][$setId]);
    }

    private function saveMatchSets($sets) {
        $data = $this->storage->getData();
        $data["matchSets"] = \array_map([$this, "normalizeSet"], $sets);
        $this->storage->saveData($data);
    }

    private function normalizeSet(MatchSet $matchSet) {
        return [
            "id" => $matchSet->getId()->getUuid(),
            "name" => $matchSet->getName(),
            "matches" => $this->normalizeMatches($matchSet->getMatches())
        ];
    }

    private function normalizeMatches($matches) {
        return \array_map(function (Match $match) {
            return [
                "id" => $match->getId()->getUuid(),
                "home" => [
                    "name" => $match->getHome()->getName(),
                    "score" => $match->getHome()->getScore()
                ],
                "away" => [
                    "name" => $match->getAway()->getName(),
                    "score" => $match->getAway()->getScore()
                ]
            ];
        }, $matches);
    }

    private function denormalizeSet($data): MatchSet {
        return new MatchSet($data["name"],
            \array_map(function ($match) {
                return new Match(
                    new MatchTeam($match["home"]["name"], $match["home"]["score"] ?? null),
                    new MatchTeam($match["away"]["name"], $match["away"]["score"] ?? null),
                    new Uuid($match["id"])
                );
            }, $data["matches"])
            , new Uuid($data["id"])
        );
    }

}
