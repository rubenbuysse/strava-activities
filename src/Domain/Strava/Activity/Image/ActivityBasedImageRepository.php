<?php

namespace App\Domain\Strava\Activity\Image;

use App\Domain\Strava\Activity\ActivityRepository;

final readonly class ActivityBasedImageRepository implements ImageRepository
{
    public function __construct(
        private ActivityRepository $activityRepository
    ) {
    }

    /**
     * @return \App\Domain\Strava\Activity\Image\Image[]
     */
    public function findAll(): array
    {
        $images = [];
        $activities = $this->activityRepository->findAll();
        foreach ($activities as $activity) {
            if (0 === $activity->getTotalImageCount()) {
                continue;
            }
            $images = [
                ...$images,
                ...array_map(
                    fn (string $path) => Image::create(
                        gitHubImageLocation: $path,
                        activity: $activity
                    ),
                    $activity->getRemoteImagePaths()
                ),
            ];
        }

        return $images;
    }
}
