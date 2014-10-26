<?php

namespace Stash;


class User
{
    /** @var string */
    private $name;

    /** @var string */
    private $displayName;

    /** @var string */
    private $id;

    /** @var RestApiClient */
    private $client;

    public function __construct(RestApiClient $client)
    {
        $this->setClient($client);
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

    public function setData(\StdClass $data)
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