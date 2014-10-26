<?php

namespace Stash;

class Repo
{
    private $id;

    private $slug;

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
        $this->setSlug($data->slug);
        $this->setlink(ltrim($data->link->url, '/'));
    }

    /**
     * @param array $statuses
     * @return PullRequest[]
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
                        $return = new PullRequest($client);
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