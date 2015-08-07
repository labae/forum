<?php namespace Boxkode\Forum\Controllers;

use App;
use Config;
use Event;
use Input;
use Illuminate\Routing\Controller;
use Redirect;
use Boxkode\Forum\Events\ThreadWasViewed;
use Boxkode\Forum\Repositories\Tags;
use Boxkode\Forum\Repositories\Threads;
use Boxkode\Forum\Repositories\Posts;
use Boxkode\Forum\Libraries\AccessControl;
use Boxkode\Forum\Libraries\Alerts;
use Boxkode\Forum\Libraries\Utils;
use Boxkode\Forum\Libraries\Validation;
use Route;
use View;
use Validator;

abstract class BaseController extends Controller {

    // Repositories
    private $tags;
    private $threads;
    private $posts;

    // Collections cache
    private $collections = array();

    public function __construct(Tags $tags, Threads $threads, Posts $posts)
    {
        $this->tags = $tags;
        $this->threads = $threads;
        $this->posts = $posts;
    }

    protected function check404()
    {
        foreach ($this->collections as $item)
        {
            if ($item == null)
            {
                App::abort(404);
            }
        }
    }

    protected function load($select = array(), $with = array())
    {
        $map_model_repos = array(
            'tag'       => 'tags',
            'thread'    => 'threads',
            'post'      => 'posts'
        );

        $map_route_models = array(
            'forum.get.view.tag'        => 'tag',
            'forum.get.create.thread'   => 'tag',
            'forum.get.view.thread'     => 'thread',
            'forum.post.lock.thread'    => 'thread',
            'forum.delete.thread'       => 'thread',
            'forum.get.edit.post'       => 'post',
            'forum.delete.post'         => 'post'
        );

        $map_route_permissions = array(
            'forum.get.view.tag'        => 'access_tag',
            'forum.get.create.thread'   => 'create_threads',
            'forum.get.view.thread'     => 'access_tag',
            'forum.post.lock.thread'    => 'lock_threads',
            'forum.delete.thread'       => 'delete_threads',
            'forum.get.edit.post'       => 'edit_post',
            'forum.delete.post'         => 'delete_posts'
        );

        $route_name = Route::current()->getName();

        foreach ($select as $model => $id)
        {
            $this->collections[$model] = $this->$map_model_repos[$model]->getByID($id, $with);

            if (isset($map_route_permissions[$route_name]) && $model == $map_route_models[$route_name])
            {
                AccessControl::check($this->collections[$model], $map_route_permissions[$route_name]);
            }
        }

        $this->check404();
    }

    protected function makeView($name)
    {
        return View::make($name)->with($this->collections);
    }

    public function getViewIndex()
    {
        $tags = $this->tags->getAll();
        $threads = $this->threads->getRecent();

        return View::make('forum::index', compact('tags','threads'));
    }

    public function getViewNew()
    {
        $user = Utils::getCurrentUser();
        $userID = (!is_null($user)) ? $user->id : 0;
        $posts = $this->posts->getNewForUser($userID);
        $threads = $this->threads->getNewForUser($userID);

        return View::make('forum::new', compact('posts','threads','user'));
    }

    public function postMarkAsRead()
    {
        $user = Utils::getCurrentUser();
        if (!is_null($user))
        {
            $posts = $this->posts->getNewForUser();

            foreach ($posts as $post)
            {
                $post->markAsRead($user->id);
            }

            Alerts::add('success', trans('forum::base.marked_read'));
        }

        return Redirect::to(config('forum.routes.root'));
    }

    public function getViewTag($tagID, $tagAlias)
    {
        $this->load(['tag' => $tagID]);

        return $this->makeView('forum::tag');
    }

    public function getViewThread($threadID, $threadAlias)
    {
        $this->load(['thread' => $threadID]);

        Event::fire(new ThreadWasViewed($this->collections['thread']));

        return $this->makeView('forum::thread-detail');
        
    }

    public function getCreateThread()
    {
        $tags = $this->tags->getAll();
        return View::make('forum::thread-create',compact('tags'));
    }

    public function postCreateThread()
    {
        $user = Utils::getCurrentUser();

        $thread_valid = Validation::check('thread');
        $post_valid = Validation::check('post');
        if ($thread_valid && $post_valid)
        {
            $thread = array(
                'author_id'       => $user->id,
                'title'           => Input::get('title')
            );

            $thread = $this->threads->create($thread);
            $thread->tags()->sync(Input::get('tags'));

            $post = array(
                'parent_thread'   => $thread->id,
                'author_id'       => $user->id,
                'content'         => Input::get('content')
            );

            $this->posts->create($post);

            Alerts::add('success', trans('forum::base.thread_created'));

            return Redirect::to($thread->route);
        }
        else
        {
            return Redirect::back()->withInput();
        }
    }

    public function getReplyToThread($threadID, $threadAlias)
    {
        $this->load(['thread' => $threadID]);

        if (!$this->collections['thread']->canReply)
        {
            return Redirect::to($this->collections['thread']->route);
        }

        return $this->makeView('forum::thread-reply');
    }

    public function postReplyToThread($threadID, $threadAlias)
    {
        $user = Utils::getCurrentUser();

        $this->load(['thread' => $threadID]);

        if (!$this->collections['thread']->canReply)
        {
            return Redirect::to($this->collections['thread']->route);
        }

        $post_valid = Validation::check('post');
        if ($post_valid)
        {
            $post = array(
                'parent_thread' => $threadID,
                'author_id'     => $user->id,
                'content'       => Input::get('content')
            );

            $post = $this->posts->create($post);

            $post->thread->touch();

            Alerts::add('success', trans('forum::base.reply_added'));

            return Redirect::to($this->collections['thread']->lastPostRoute);
        }
        else
        {
            return Redirect::to($this->collections['thread']->replyRoute)->withInput();
        }
    }

    public function postLockThread($threadID, $threadAlias)
    {
        $this->load(['thread' => $threadID]);

        $this->collections['thread']->toggle('locked');

        return Redirect::to($this->collections['thread']->route);
    }


    public function deleteThread($threadID, $threadAlias)
    {
        $this->load(['thread' => $threadID]);

        if (config('forum.preferences.soft_delete'))
        {
            $this->collections['thread']->posts()->delete();
        }
        else
        {
            $this->collections['thread']->posts()->forceDelete();
        }

        $this->threads->delete($threadID);

        Alerts::add('success', trans('forum::base.thread_deleted'));

        return Redirect::to($this->collections['tag']->route);
    }

    public function getEditPost($threadID, $threadAlias, $postID)
    {
        $this->load(['thread' => $threadID, 'post' => $postID]);

        return $this->makeView('forum::post-edit');
    }

    public function postEditPost($threadID, $threadAlias, $postID)
    {
        $user = Utils::getCurrentUser();

        $this->load(['thread' => $threadID, 'post' => $postID]);

        $post_valid = Validation::check('post');
        if ($post_valid)
        {
            $post = array(
                'id'            => $postID,
                'parent_thread' => $threadID,
                'author_id'     => $user->id,
                'content'       => Input::get('content')
            );

            $post = $this->posts->update($post);

            Alerts::add('success', trans('forum::base.post_updated'));

            return Redirect::to($post->route);
        }
        else
        {
            return Redirect::to($this->collections['post']->editRoute)->withInput();
        }
    }

    public function deletePost($threadID, $threadAlias, $postID)
    {
        $this->load(['thread' => $threadID, 'post' => $postID]);

        $this->posts->delete($postID);

        Alerts::add('success', trans('forum::base.post_deleted'));

        // Force deletion of the thread if it has no remaining posts
        if ($this->collections['thread']->posts->count() == 0)
        {
            $this->threads->delete($threadID);

            return Redirect::to($this->collections['tag']->route);
        }

        return Redirect::to($this->collections['thread']->route);
    }

}
