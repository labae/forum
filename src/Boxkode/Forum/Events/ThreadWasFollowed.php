<?php namespace Boxkode\Forum\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Session\Store;
use Boxkode\Forum\Models\Thread;

class ThreadWasFollowed extends BaseEvent {

    use SerializesModels;

    public $thread;

    /**
     * Create a new event instance.
     *
     * @param  Thread  $thread
     * @return void
     */
    public function __construct(Thread $thread)
    {
        $this->thread = $thread;
    }

}
