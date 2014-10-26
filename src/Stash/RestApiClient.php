<?php

namespace Stash;

class RestApiClient
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