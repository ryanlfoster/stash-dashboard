<?php

namespace Stash;

class Activity
{
    /** @var RestApiClient */
    private $client;

    private $id;

    private $createdDate;

    /** @var User */
    private $user;

    private $action;

    private $commentAction;

    /** @var Comment */
    private $comment;

    public function __construct(RestApiClient $client)
    {
        $this->setClient($client);
    }

    public function setData(\StdClass $data)
    {
        $this->setId($data->id);
        $this->setCreatedDate($data->createdDate / 1000);
        $this->setAction($data->action);
        if (isset($data->commentAction)) {
            $this->setCommentAction($data->commentAction);
        }
        if (isset($data->comment)) {
            $comment = new Comment($this->getClient());
            $comment->setData($data->comment);
            $this->setComment($comment);
        }
        $user = new User($this->getClient());
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
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
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
     * @return Comment
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param Comment $comment
     */
    public function setComment(Comment $comment)
    {
        $this->comment = $comment;
    }
}