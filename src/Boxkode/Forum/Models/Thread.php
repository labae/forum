<?php namespace Boxkode\Forum\Models;

use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Boxkode\Forum\Libraries\AccessControl;
use Boxkode\Forum\Libraries\Alerts;
use Boxkode\Forum\Libraries\Utils;
use Boxkode\Forum\Models\Traits\HasAuthor;
use Illuminate\Support\Collection;
use Parsedown;

class Thread extends BaseModel {

    use SoftDeletes, HasAuthor;

    // Eloquent properties
    protected $table         = 'forum_threads';
    public    $timestamps    = true;
    protected $dates         = ['deleted_at'];
    protected $appends       = ['lastPage', 'lastPost', 'lastPostRoute', 'route', 'lockRoute', 'replyRoute', 'deleteRoute'];
    protected $hidden        = ['lastPost'];
    protected $guarded       = ['id'];
    protected $with          = ['author'];

    // Thread constants
    const     STATUS_UNREAD  = 'unread';
    const     STATUS_UPDATED = 'updated';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tags()
    {
        return $this->belongsToMany('\Boxkode\Forum\Models\Tag','forum_tags_threads','thread_id','tag_id')->withTimestamps();
    }

    public function readers()
    {
        return $this->belongsToMany(config('forum.integration.user_model'), 'forum_threads_follow', 'thread_id', 'user_id')->withTimestamps();
    }

    public function posts()
    {
        return $this->hasMany('\Boxkode\Forum\Models\Post', 'parent_thread');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeRecent($query)
    {
        $cutoff = config('forum.preferences.thread.cutoff_age');
        return $query->where('updated_at', '>', date('Y-m-d H:i:s', strtotime($cutoff)));
    }

    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    // Route attributes

    public function getRouteAttribute()
    {
        return $this->getRoute('forum.get.view.thread');
    }

    public function getReplyRouteAttribute()
    {
        return $this->getRoute('forum.get.reply.thread');
    }

    public function getLockRouteAttribute()
    {
        return $this->getRoute('forum.post.lock.thread');
    }

    public function getDeleteRouteAttribute()
    {
        return $this->getRoute('forum.delete.thread');
    }

    public function getLastPostRouteAttribute()
    {
        return "{$this->route}?page={$this->lastPage}#post-{$this->lastPost->id}";
    }

    // General attributes

    public function getPostsPaginatedAttribute()
    {
        return $this->posts()->paginate(config('forum.preferences.posts_per_thread'));
    }

    public function getPageLinksAttribute()
    {
        return $this->postsPaginated->render();
    }

    public function getLastPageAttribute()
    {
        return $this->postsPaginated->lastPage();
    }

    public function getLastPostAttribute()
    {
        return $this->posts()->orderBy('created_at', 'desc')->first();
    }
    public function getQuestionAttribute()
    {
        return $this->posts()->orderBy('created_at', 'asc')->first();
    }
    public function getAnswerAttribute()
    {
        $first = $this->question;
        return $this->posts()->orderBy('created_at','asc')->whereNotIn('id',[$first->id])->get();
    }

    public function getLastPostTimeAttribute()
    {
        return $this->lastPost->created_at;
    }

    public function getReplyCountAttribute()
    {
        return ($this->posts->count() - 1);
    }

    public function getOldAttribute()
    {
        $cutoff = config('forum.preferences.thread.cutoff_age');
        return (!$cutoff || $this->updated_at->timestamp < strtotime($cutoff));
    }

    public function getViewCountAttribute()
    {
        return $this->attributes['view_count'];
    }

    // Current user: reader attributes

    public function getReaderAttribute()
    {
        if (!is_null(Utils::getCurrentUser()))
        {
            $reader = $this->readers()->where('user_id', '=', Utils::getCurrentUser()->id)->first();

            return (!is_null($reader)) ? $reader->pivot : null;
        }

        return null;
    }

    public function getUserReadStatusAttribute()
    {
        if (!$this->old && !is_null(Utils::getCurrentUser()))
        {
            if (is_null($this->reader))
            {
                return self::STATUS_UNREAD;
            }

            return ($this->updatedSince($this->reader)) ? self::STATUS_UPDATED : false;
        }

        return false;
    }

    public function getKeywordsAttribute()
    {
        $keywords = array();
        foreach($this->tags()->get() as $tag)
            $keywords[] = $tag->title;

        return implode(',', $keywords);
    }

    public function getDescriptionAttribute()
    {
        $description = null;
        $content = $this->getQuestionAttribute()->content;

        return str_limit(e($content),'255');
    }

    // Current user: permission attributes

    public function getUserCanReplyAttribute()
    {
        return AccessControl::check($this, 'reply_to_thread', false);
    }

    public function getCanReplyAttribute()
    {
        return $this->userCanReply;
    }

    public function getUserCanLockAttribute()
    {
        return AccessControl::check($this, 'lock_threads', false);
    }

    public function getCanLockAttribute()
    {
        return $this->userCanLock;
    }

    public function getUserCanDeleteAttribute()
    {
        return AccessControl::check($this, 'delete_threads', false);
    }

    public function getCanDeleteAttribute()
    {
        return $this->userCanDelete;
    }

    

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function getRouteComponents()
    {
        $components = array(
            'threadID'      => $this->id,
            'threadAlias'   => Str::slug($this->title, '-')
        );

        return $components;
    }

    public function markAsRead($userID)
    {
        if (!$this->old)
        {
            if (is_null($this->reader))
            {
                $this->readers()->attach($userID);
            }
            elseif ($this->updatedSince($this->reader))
            {
                $this->reader->touch();
            }
        }
    }

    public function toggle($property)
    {
        parent::toggle($property);

        Alerts::add('success', trans('forum::base.thread_updated'));
    }

    /**
     * Costumize
     */
   

    /*-------------------------------------------------------------------------
     * Scope
     *-------------------------------------------------------------------------
     */
    public function scopeDeactive($qry)
    {
        return $qry->where('deleted_at','<>',NULL);
    }

    public function scopeActive($qry)
    {
        return $qry->where('deleted_at','=',NULL);
    }

    public function scopePopular($qry)
    {
        return $qry->orderBy();
    }


}
