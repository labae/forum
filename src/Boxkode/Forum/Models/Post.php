<?php namespace Boxkode\Forum\Models;

use DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Boxkode\Forum\Libraries\AccessControl;
use Boxkode\Forum\Libraries\Alerts;
use Boxkode\Forum\Libraries\Utils;
use Boxkode\Forum\Models\Traits\HasAuthor;
use Parsedown;

class Post extends BaseModel {

    use SoftDeletes, HasAuthor;

    // Eloquent properties
    protected $table      = 'forum_posts';
    public    $timestamps = true;
    protected $dates      = ['deleted_at'];
    protected $appends    = ['route', 'editRoute'];
    protected $with       = ['author'];
    protected $guarded    = ['id'];

    // Post constants
    const     STATUS_UNREAD  = 'unread';
    const     STATUS_UPDATED = 'updated';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function thread()
    {
        return $this->belongsTo('\Boxkode\Forum\Models\Thread', 'parent_thread');
    }

    public function readers()
    {
        return $this->belongsToMany(config('forum.integration.user_model'), 'forum_posts_read', 'post_id', 'user_id')->withTimestamps();
    }
    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    // Route attributes

    public function getRouteAttribute()
    {
        $perPage = config('forum.preferences.posts_per_thread');
        $count = $this->thread->posts()->where('id', '<=', $this->id)->paginate($perPage)->total();
        $page = ceil($count / $perPage);

        return "{$this->thread->route}?page={$page}#post-{$this->id}";
    }

    public function getEditRouteAttribute()
    {
        return $this->getRoute('forum.get.edit.post');
    }

    public function getDeleteRouteAttribute()
    {
        return $this->getRoute('forum.get.delete.post');
    }

    // Current user: permission attributes

    public function getUserCanEditAttribute()
    {
        return AccessControl::check($this, 'edit_post', false);
    }

    public function getCanEditAttribute()
    {
        return $this->userCanEdit;
    }

    public function getUserCanDeleteAttribute()
    {
        return AccessControl::check($this, 'delete_posts', false);
    }

    public function getCanDeleteAttribute()
    {
        return $this->userCanDelete;
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

    public function getParsedownAttribute()
    {
        return Parsedown::text($this->attributes['content']);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

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

    protected function getRouteComponents()
    {
        $components = array(
            'threadID'      => $this->thread->id,
            'threadAlias'   => Str::slug($this->thread->title, '-'),
            'postID'        => $this->id
        );

        return $components;
    }

}
