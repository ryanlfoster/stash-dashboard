<?php
set_time_limit(0);

require_once('vendor/autoload.php');
require_once('config.php');

header('Content-type: application/json');

$client = new Stash\RestApiClient(STASH_API_URL, STASH_API_USERNAME, STASH_API_PASSWORD);

$project = new Stash\Project($client);
$project = $project->getByKey(STASH_API_PROJECT);

$repositories = $project->getRepositories();

if (isset($repositoryNames) && !empty($repositoryNames)) {
    $repositories = array_filter(
        $repositories,
        function(\Stash\Repo $value) use ($repositoryNames) {
            return in_array(
                $value->getSlug(),
                $repositoryNames
            );
        }
    );
}

$output = array(
    'requests' => array(),
    'firstToReact' => array(),
    'approves' => array(),
    'comments' => array(),
    'merges' => array()
);

function dolog($msg, $level = 1)
{
    fwrite(STDERR, str_repeat(' ', $level) . $msg . PHP_EOL);
}

$calculator = new \DateTime\WorkingHoursCalculator(WORKDAY_BEGIN_HOUR, WORKDAY_END_HOUR, $holidays);

foreach($repositories as $repository) {
    $row = new \StdClass();

    /** @var Stash\Repo $repository */
    $requests = $repository->getPullRequests(array('MERGED'));

    /** @var Stash\PullRequest $request */
    foreach($requests as $request) {
        $row->repo = $repository->getSlug();
        $row->week = date('Y', $request->getCreatedDate()) . 'w' . str_pad(date('W', $request->getCreatedDate()), 2, '0', STR_PAD_LEFT);
        $row->day = date('Y-m-d', $request->getCreatedDate());
        $row->createdDate = $request->getCreatedDate();
        $row->id = $request->getId();
        $row->authorName = $request->getAuthor()->getName();
        $row->authorDisplayName = $request->getAuthor()->getDisplayName();
        $output['requests'][] = clone $row;
        $activities = array_reverse($request->getActivities());

        $minInterval = PHP_INT_MAX;
        $firstToReact = null;
        /** @var Stash\Activity $activity */
        foreach($activities as $activity) {
            if ( (
                    $activity->getAction() == 'COMMENTED' &&
                    $activity->getCommentAction() == 'ADDED')
                || $activity->getAction() == 'APPROVED'
                ) {
                if ($activity->getCreatedDate() - $row->createdDate < $minInterval) {
                    $firstToReact = clone $activity;
                    $minInterval = $firstToReact->getCreatedDate() - $request->getCreatedDate();
                }
            }
        }
        if ($firstToReact) {
            $firstToReactRow = clone $row;
            $firstToReactRow->firstToReactName = $firstToReact->getUser()->getName();
            $firstToReactRow->firstToReactDisplayName = $firstToReact->getUser()->getDisplayName();
            // TODO: calculate working hours to react?
            $firstToReactRow->firstToReactInterval = $minInterval;
            $firstToReactRow->firstToReactIntervalWorkingHrs = $calculator->getWorkingHours($request->getCreatedDate(), $request->getCreatedDate() + $minInterval);
            $output['firstToReact'][] = clone $firstToReactRow;
        }


        /** @var Stash\Activity $activity */
        foreach($activities as $activity) {
            if ($activity->getAction() == 'COMMENTED' &&
                $activity->getCommentAction() == 'ADDED'
            ) {
                $commentRow = clone $row;
                $commentRow->commentName = $activity->getUser()->getName();
                $commentRow->commentDisplayName = $activity->getUser()->getDisplayName();
                $commentRow->commentTimeToReact = $activity->getCreatedDate() - $request->getCreatedDate();
                $commentRow->commentLength = strlen($activity->getComment()->getText());
                $output['comments'][] = clone $commentRow;
            }
        }

        /** @var Stash\Activity $activity */
        foreach($activities as $activity) {
            if (
                    $activity->getAction() == 'APPROVED'
            ) {
                $approvedRow = clone $row;
                $approvedRow->approveName = $activity->getUser()->getName();
                $approvedRow->approveDisplayName = $activity->getUser()->getDisplayName();
                $approvedRow->approveTimeToReact = $activity->getCreatedDate() - $request->getCreatedDate();
                $approvedRow->approveTimeToReactWorkingHrs = $calculator->getWorkingHours($request->getCreatedDate(), $activity->getCreatedDate());
                $output['approves'][] = clone $approvedRow;
            }
        }

        /** @var Stash\Activity $activity */
        foreach($activities as $activity) {
            if (
                $activity->getAction() == 'MERGED'
            ) {
                $mergeRow = clone $row;
                $mergeRow->mergeInterval = $activity->getCreatedDate() - $request->getCreatedDate();
                $output['merges'][] = $mergeRow;
            }
        }
    }

}
echo json_encode($output);
