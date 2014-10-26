<?php
namespace Stash;

class Project
{
    private $id;

    private $key;

    private $link;

    /** @var RestApiClient */
    private $client;

    public function __construct(RestApiClient $client)
    {
        $this->setClient($client);
    }

    public function setData(\StdClass $data)
    {
        $this->setId($data->id);
        $this->setKey($data->key);
        $this->setLink(ltrim($data->link->url, '/'));
    }

    /**
     * @param string $key
     * @return Project
     */
    public function getByKey($key)
    {
        $project = new Project($this->getClient());
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
            $return = new Repo($client);
            $return->setData($value);
            return $return;
        });
    }

    /**
     * @return RestApiClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param RestApiClient $client
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