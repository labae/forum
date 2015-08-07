<?php namespace Boxkode\Forum\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Session\Store;
use Boxkode\Forum\Models\Post;

class PostWasViewed extends BaseEvent {

    use SerializesModels;

    public $post;

    /**
     * Create a new event instance.
     *
     * @param  Post  $post
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

}
