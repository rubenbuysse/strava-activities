<?php

namespace App\Domain\Strava\Gear;

use App\Domain\Strava\Activity\Activity;
use App\Domain\Strava\Activity\ActivityCollection;
use Carbon\CarbonInterval;

final readonly class GearStatistics
{
    private function __construct(
        private ActivityCollection $activities,
        private GearCollection $bikes,
    ) {
    }

    public static function fromActivitiesAndGear(
        ActivityCollection $activities,
        GearCollection $bikes): self
    {
        return new self($activities, $bikes);
    }

    /**
     * @return array<mixed>
     */
    public function getRows(): array
    {
        $statistics = array_map(function (Gear $bike) {
            $activitiesWithBike = array_filter($this->activities->toArray(), fn (Activity $activity) => $activity->getGearId() == $bike->getId());
            $countActivitiesWithBike = count($activitiesWithBike);
            $movingTimeInSeconds = array_sum(array_map(fn (Activity $activity) => $activity->getMovingTimeInSeconds(), $activitiesWithBike));

            return [
                'name' => $bike->getName(),
                'distance' => $bike->getDistanceInKm(),
                'numberOfRides' => $countActivitiesWithBike,
                'movingTime' => CarbonInterval::seconds($movingTimeInSeconds)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
                'elevation' => array_sum(array_map(fn (Activity $activity) => $activity->getElevation(), $activitiesWithBike)),
                'averageDistance' => $countActivitiesWithBike > 0 ? $bike->getDistanceInKm() / $countActivitiesWithBike : 0,
                'averageSpeed' => $movingTimeInSeconds > 0 ? ($bike->getDistanceInKm() / $movingTimeInSeconds) * 3600 : 0,
                'totalCalories' => array_sum(array_map(fn (Activity $activity) => $activity->getCalories(), $activitiesWithBike)),
            ];
        }, $this->bikes->toArray());

        $activitiesWithOtherBike = array_filter($this->activities->toArray(), fn (Activity $activity) => empty($activity->getGearId()));
        $countActivitiesWithOtherBike = count($activitiesWithOtherBike);
        if (0 === $countActivitiesWithOtherBike) {
            return $statistics;
        }
        $distanceWithOtherBike = array_sum(array_map(fn (Activity $activity) => $activity->getDistance(), $activitiesWithOtherBike));
        $movingTimeInSeconds = array_sum(array_map(fn (Activity $activity) => $activity->getMovingTimeInSeconds(), $activitiesWithOtherBike));

        $statistics[] = [
            'name' => 'Other',
            'distance' => array_sum(array_map(fn (Activity $activity) => $activity->getDistance(), $activitiesWithOtherBike)),
            'numberOfRides' => $countActivitiesWithOtherBike,
            'movingTime' => CarbonInterval::seconds($movingTimeInSeconds)->cascade()->forHumans(['short' => true, 'minimumUnit' => 'minute']),
            'elevation' => $distanceWithOtherBike,
            'averageDistance' => $distanceWithOtherBike / $countActivitiesWithOtherBike,
            'averageSpeed' => ($distanceWithOtherBike / $movingTimeInSeconds) * 3600,
            'totalCalories' => array_sum(array_map(fn (Activity $activity) => $activity->getCalories(), $activitiesWithOtherBike)),
        ];

        return $statistics;
    }
}