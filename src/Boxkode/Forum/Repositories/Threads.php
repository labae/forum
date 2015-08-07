<?php namespace Boxkode\Forum\Repositories;

use Boxkode\Forum\Models\Thread;

class Threads extends BaseRepository {

    public function __construct(Thread $model)
    {
        $this->model = $model;

        $this->itemsPerPage = config('forum.integration.threads_per_category');
    }

    public function getRecent($where = array())
    {
        return $this->model->with('tags', 'posts')->recent()->where($where)->orderBy('updated_at', 'desc')->get();
    }

    public function getNewForUser($userID = 0, $where = array())
    {
        $threads = $this->getRecent($where);

        // If we have a user ID, filter the threads appropriately
        if ($userID)
        {
            $threads = $threads->filter(function($thread)
            {
                return $thread->userReadStatus;
            });
        }


        return $threads;
    }

    public function getThread($id)
    {
        return $this->model->find($id);
    }

    public function getRecentPopular($where = array())
    {
        $sort = $this->getRecent($where);
        $sort = $sort->sortByDesc('reply_count');
        return $sort->values()->all();
    }

}
