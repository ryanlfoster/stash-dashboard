<?php

namespace Stash;


class Comment
{
    /** @var RestApiClient */
    private $client;

    private $createdDate;

    private $updatedDate;

    private $text;

    private $id;

    private $comments;

    /** @var User */
    private $author;

    public function __construct(RestApiClient $client)
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
                $comment = new Comment($this->getClient());
                $comment->setData($commentData);
                $this->comments[] = $comment;
            }
        }
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


}