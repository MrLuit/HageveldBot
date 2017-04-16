<?php

namespace InstagramAPI\Response\Model;

class Args extends \InstagramAPI\Response
{
    /**
     * @var Media[]
     */
    public $media;
    /**
     * @var Link[]
     */
    public $links;
    public $text;
    /**
     * @var string
     */
    public $profile_id;
    public $profile_image;
    public $timestamp;
    /**
     * @var string
     */
    public $comment_id;
    public $request_count;
    public $action_url;
    public $destination;
    /**
     * @var InlineFollow
     */
    public $inline_follow;
}
