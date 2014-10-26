<?php

namespace Stash;

class PullRequest
{
    private $id;

    private $createdDate;

    private $updatedDate;

    private $state;

    private $link;

    private $author;

    /** @var RestApiClient */
    private $client;

    public function __construct(RestApiClient $client = null)
    {
        $this->setClient($client);
    }

    public function setData(\StdClass $data)
    {
        $this->setId($data->id);
        $this->setCreatedDate($data->createdDate / 1000);
        $this->setUpdatedDate($data->updatedDate / 1000);
        $this->setState($data->state);
        $this->setLink(ltrim($data->link->url, '/'));

        $author = new User($this->getClient());
        $author->setData($data->author->user);
        $this->setAuthor($author);
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

    public function getActivities()
    {
        $client = $this->getClient();
        return $this->client->getPagedResult(
            $this->getLink() . '/activities',
            array(),
            function($value) use ($client) {
                $return = new Activity($client);
                $return->setData($value);
                return $return;
            },
            in_array($this->getState(), array('MERGED', 'DECLINED')) // cache merged and declined PRs forever
        );
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param User $author
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