<?php namespace Boxkode\Forum\Repositories;

use Boxkode\Forum\Models\Post;

class Posts extends BaseRepository {

    public function __construct(Post $model)
    {
        $this->model = $model;

        $this->itemsPerPage = config('forum.integration.posts_per_thread');
    }

    public function getLastByThread($threadID, $count = 10, array $with = array())
    {
        $model = $this->model->where('parent_thread', '=', $threadID);
        $model = $model->orderBy('created_at', 'DESC')->take($count);
        $model = $model->with($with);

        return $model;
    }

    public function getRecent($where = array(), $count = 10)
    {
        $model = $this->model->orderBy('created_at','DESC')->get();
        return $model;
    }

    public function getNewForUser($userID = 0, $where = array())
    {
        $posts = $this->getRecent($where);

        // If we have a user ID, filter the posts appropriately
        if ($userID)
        {
            $posts = $posts->filter(function($post)
            {
                return $post->userReadStatus;
            });
        }

        // Filter the posts according to the user's permissions
        $posts = $posts->filter(function($post)
        {
            return $post->thread->UserCanReply;
        });

        return $posts;
    }

}
