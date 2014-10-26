<?php
set_time_limit(0);

require_once('vendor/autoload.php');
require_once('config.php');

header('Content-type: application/json');

$client = new StashRestApiClient(STASH_API_URL, STASH_API_USERNAME, STASH_API_PASSWORD);

$project = new StashProject($client);
$project = $project->getByKey(STASH_API_PROJECT);

$repositories = $project->getRepositories();

if (defined($repositoryNames) && !empty($repositoryNames)) {
    $repositories = array_filter(
        $repositories,
        function(StashRepo $value) {
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
foreach($repositories as $repository) {
    $row = new \StdClass();

    /** @var StashRepo $repository */
    $requests = $repository->getPullRequests(array('MERGED'));

    /** @var StashPullRequest $request */
    foreach($requests as $request) {
        // echo $request->getId() . PHP_EOL;
        $row->repo = $repository->getSlug();
        $row->week = (int) date('W', $request->getCreatedDate());
        $row->day = date('Y-m-d', $request->getCreatedDate());
        $row->createdDate = $request->getCreatedDate();
        $row->id = $request->getId();
        $row->authorName = $request->getAuthor()->getName();
        $row->authorDisplayName = $request->getAuthor()->getDisplayName();
        $output['requests'][] = clone $row;
        $activities = array_reverse($request->getActivities());

        $minInterval = PHP_INT_MAX;
        $firstToReact = null;
        /** @var StashActivity $activity */
        foreach($activities as $activity) {
            // echo ($activity->getAction() . PHP_EOL);
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
            $firtToReactRow = clone $row;
            $firtToReactRow->firstToReactName = $firstToReact->getUser()->getName();
            $firtToReactRow->firstToReactDisplayName = $firstToReact->getUser()->getDisplayName();
            $firtToReactRow->firstToReactInterval = $minInterval;
            $output['firstToReact'][] = clone $firtToReactRow;
        }


        /** @var StashActivity $activity */
        foreach($activities as $activity) {
            if ($activity->getAction() == 'COMMENTED' &&
                $activity->getCommentAction() == 'ADDED'
            ) {
                $commentRow = clone $row;
                $commentRow->commentName = $activity->getUser()->getName();
                $commentRow->commentDisplayName = $activity->getUser()->getDisplayName();
                $commentRow->commentTimeToReact = $activity->getCreatedDate() - $request->getCreatedDate();
                $output['comments'][] = clone $commentRow;
            }
        }

        /** @var StashActivity $activity */
        foreach($activities as $activity) {
            if (
                    $activity->getAction() == 'APPROVED'
            ) {
                $approvedRow = clone $row;
                $approvedRow->approveName = $activity->getUser()->getName();
                $approvedRow->approveDisplayName = $activity->getUser()->getDisplayName();
                $approvedRow->approveTimeToReact = $activity->getCreatedDate() - $request->getCreatedDate();
                $output['approves'][] = clone $approvedRow;
            }
        }

        /** @var StashActivity $activity */
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

class StashProject
{
    private $id;

    private $key;

    private $link;

    /** @var StashRestApiClient */
    private $client;

    public function __construct(StashRestApiClient $client)
    {
        $this->setClient($client);
    }

    public function setData(stdClass $data)
    {
        $this->setId($data->id);
        $this->setKey($data->key);
        $this->setLink(ltrim($data->link->url, '/'));
    }

    /**
     * @param string $key
     * @return StashProject
     */
    public function getByKey($key)
    {
        $project = new StashProject($this->getClient());
        $project->setData($this->getClient()->getJson("projects/{$key}"));

        return $project;
    }

    /**
     * @return StashRepo[]
     */
    public function getRepositories()
    {
        $client = $this->getClient();
        return $client->getPagedResult($this->getLink() . '/repos', array(), function($value) use ($client) {
           $return = new StashRepo($client);
            $return->setData($value);
            return $return;
        });
    }

    /**
     * @return StashRestApiClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param StashRestApiClient $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param mixed $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}

class StashRepo
{
    private $id;

    private $slug;

    private $link;

    /** @var StashRestApiClient */
    private $client;

    public function __construct(StashRestApiClient $client)
    {
        $this->setClient($client);
    }

    public function setData(stdClass $data)
    {
        $this->setId($data->id);
        $this->setSlug($data->slug);
        $this->setlink(ltrim($data->link->url, '/'));
    }

    /**
     * @param array $statuses
     * @return StashPullRequest[]
     */
    public function getPullRequests(array $states = array('ALL') )
    {
        $client = $this->getClient();

        $return = array();
        foreach($states as $state) {
            $canBeUpdated = ($state != 'MERGED' && $state != 'DECLINED');
            $return = array_merge(
                $return,
                $this->getClient()->getPagedResult(
                        preg_replace('#/browse$#', '', $this->link) . '/pull-requests', array('state' => $state),
                        function($value) use ($client) {
                            $return = new StashPullRequest($client);
                            $return->setData($value);
                            return $return;
                        }
                    , !$canBeUpdated
                )
            );
        }
        return $return;

    }

    /**
     * @return StashRestApiClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param StashRestApiClient $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param mixed $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

}

class StashPullRequest
{
    private $id;

    private $createdDate;

    private $updatedDate;

    private $state;

    private $link;

    private $author;

    /** @var StashRestApiClient */
    private $client;

    public function __construct(StashRestApiClient $client = null)
    {
       $this->setClient($client);
    }

    public function setData(stdClass $data)
    {
        $this->setId($data->id);
        $this->setCreatedDate($data->createdDate / 1000);
        $this->setUpdatedDate($data->updatedDate / 1000);
        $this->setState($data->state);
        $this->setLink(ltrim($data->link->url, '/'));

        $author = new StashUser($this->getClient());
        $author->setData($data->author->user);
        $this->setAuthor($author);
    }

    /**
     * @return StashRestApiClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param StashRestApiClient $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    public function getActivities()
    {
        $client = $this->getClient();
        return $this->client->getPagedResult(
            $this->getLink() . '/activities',
            array(),
            function($value) use ($client) {
                $return = new StashActivity($client);
                $return->setData($value);
                return $return;
            },
            in_array($this->getState(), array('MERGED', 'DECLINED')) // cache merged and declined PRs forever
        );
    }

    /**
     * @return StashUser
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param StashUser $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @param mixed $createdDate
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param mixed $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getUpdatedDate()
    {
        return $this->updatedDate;
    }

    /**
     * @param mixed $updatedDate
     */
    public function setUpdatedDate($updatedDate)
    {
        $this->updatedDate = $updatedDate;
    }
}
class StashComment
{
    /** @var StashRestApiClient */
    private $client;

    private $createdDate;

    private $updatedDate;

    private $text;

    private $id;

    private $comments;

    /** @var StashUser */
    private $author;

    public function __construct(StashRestApiClient $client)
    {
        $this->setClient($client);
    }

    public function setData($data)
    {
        $this->setId($data->id);
        $this->setCreatedDate($data->createdDate / 1000);
        $this->setUpdatedDate($data->updatedDate / 1000);
        $this->setText($data->text);
        $this->setAuthor($data->author);
        if (isset($data->comments)) {
            foreach($data->comments as $commentData) {
                $comment = new StashComment($this->getClient());
                $comment->setData($commentData);
                $this->comments[] = $comment;
            }
        }
    }

    /**
     * @return StashRestApiClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param StashRestApiClient $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param mixed $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @param mixed $createdDate
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param mixed $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return mixed
     */
    public function getUpdatedDate()
    {
        return $this->updatedDate;
    }

    /**
     * @param mixed $updatedDate
     */
    public function setUpdatedDate($updatedDate)
    {
        $this->updatedDate = $updatedDate;
    }

    /**
     * @return StashUser
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param StashUser $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }


}

class StashActivity
{
    /** @var StashRestApiClient */
    private $client;

    private $id;

    private $createdDate;

    /** @var StashUser */
    private $user;

    private $action;

    private $commentAction;

    private $comment;

    public function __construct(StashRestApiClient $client)
    {
        $this->setClient($client);
    }

    public function setData(stdClass $data)
    {
        $this->setId($data->id);
        $this->setCreatedDate($data->createdDate / 1000);
        $this->setAction($data->action);
        if (isset($data->commentAction)) {
            $this->setCommentAction($data->commentAction);
        }
        if (isset($data->comment)) {

        }
        $user = new StashUser($this->getClient());
        $user->setData($data->user);
        $this->setUser($user);
    }

    /**
     * @return mixed
     */
    public function getCommentAction()
    {
        return $this->commentAction;
    }

    /**
     * @param mixed $commentAction
     */
    public function setCommentAction($commentAction)
    {
        $this->commentAction = $commentAction;
    }

    /**
     * @return StashUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param StashUser $user
     */
    public function setUser(StashUser $user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return StashRestApiClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param StashRestApiClient $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @param mixed $createdDate
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


}
class StashUser
{
    /** @var string */
    private $name;

    /** @var string */
    private $displayName;

    /** @var string */
    private $id;

    /** @var StashRestApiClient */
    private $client;

    public function __construct(StashRestApiClient $client)
    {
        $this->setClient($client);
    }

    /**
     * @return StashRestApiClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param StashRestApiClient $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    public function setData(stdClass $data)
    {
        $this->setId($data->id);
        $this->setName($data->name);
        $this->setDisplayName($data->displayName);
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}

class StashRestApiClient
{
    /** @var \Guzzle\Http\Client */
    private $client;

    public function __construct($baseUrl, $username, $password)
    {
        $this->client = new \Guzzle\Http\Client($baseUrl);
        $this->client->setDefaultOption('auth', array($username, $password));
    }

    public function getProjects()
    {
        return $this->getPagedResult('projects/');
    }

    public function getRepositories($projectKey)
    {
        return $this->getPagedResult("projects/{$projectKey}/repos/");
    }

    public function getPullRequests($projectKey, $repoSlug)
    {
        $values= array();
        foreach(array('OPEN', 'DECLINED') as $state) {
            $values = array_merge(
                $values,
                $this->getPagedResult(
                    "projects/{$projectKey}/repos/{$repoSlug}/pull-requests",
                    array('state' => $state)
                )
            );
        }

        return $values;
    }

    public function getActivities($pullRequestLink)
    {
        return $this->getPagedResult($pullRequestLink . '/activities');
    }

    public function getJson($relativeUri, $parameters = array(), $conversionFunction = null, $forceCache = false)
    {
        $body = null;

        dolog('Cache try', 3);
        $body = $this->getFromCache($relativeUri, $parameters, $forceCache);

        if (!$body) {
            $request = $this->client->get($relativeUri, array(), array('query' => $parameters));
            $response = $request->send();
            $body = json_decode($response->getBody(true));

            $this->saveToCache($relativeUri, $parameters, $body);
        }

        if ($conversionFunction) {
            $body = call_user_func($conversionFunction, $body);
        }
        return $body;
    }

    public function getPagedResult($relativeUri, $parameters = array(), $conversionFunction = null, $useCache = false)
    {
        $allValues = array();
        $start = 0;
        dolog(__FUNCTION__ . ': ' . $relativeUri, 1);
        do {
            $parameters['start'] = $start;
            dolog('start: ' . $start, 2);
            $body = $this->getJson($relativeUri, $parameters, null, $useCache);
            $values = $body->values;
            if ($conversionFunction) {
                $values = array_map($conversionFunction, $values);
            }
            $allValues = array_merge($allValues, $values);
            if ($body->isLastPage === false) {
                if (isset($body->nextPageStart)) {
                    $start = $body->nextPageStart;
                } else {
                    $start = $start + $body->size;
                }
            }
        } while($body->isLastPage === false);

        return $allValues;
    }

    private function saveToCache($relativeUri, $parameters, $body)
    {
        $key = md5($relativeUri . print_r($parameters, true));
        $cacheFilename = 'data/'. $key;

        return file_put_contents($cacheFilename, serialize($body));
    }

    private function getFromCache($relativeUri, $parameters, $forceCache)
    {
        // TODO: cache class
        $key = md5($relativeUri . print_r($parameters, true));
        $cacheFilename = 'data/'. $key;
        if (file_exists($cacheFilename) && ($forceCache || filemtime($cacheFilename) > time() - 3600)) {
            dolog("Cache hit", 3);
            return unserialize(file_get_contents($cacheFilename));
        }

        return false;
    }
}