<?php namespace Boxkode\Forum\Models;

use DB;
use Illuminate\Support\Str;
use Boxkode\Forum\Models\Thread;
use Boxkode\Forum\Models\Traits\HasAuthor;

class Tag extends BaseModel
{
	use HasAuthor;
    // Attributes
	protected	$table 			= 'forum_tags';
	public 		$timestamps 	= true;
	protected 	$guarded 		= ['id'];
    protected 	$with 			= ['author'];
    protected   $hidden         = ['content'];
    protected   $appends        = ['content_limiter'];

    // Relationship
    public function threads()
    {
    	return $this->belongsToMany('\Boxkode\Forum\Models\Thread','forum_tags_threads','tag_id','thread_id')->withTimestamps();
    }


    // Mutator
    public function getThreadCountAttribute()
    {
    	return $this->threads()->count();
    }

    // Current user: permission attributes

    public function getUserCanViewAttribute()
    {
        return AccessControl::check($this, 'access_tag', false);
    }

    public function getCanViewAttribute()
    {
        return $this->userCanView;
    }

    public function getUserCanPostAttribute()
    {
        return AccessControl::check($this, 'create_threads', false);
    }

    public function getCanPostAttribute()
    {
        return $this->userCanPost;
    }

    public function getContentLimiterAttribute()
    {
        return str_limit($this->attributes['content'],60);
    }

    public function getRouteAttribute()
    {
        return route('forum.get.view.tag',['tagID'=>$this->attributes['id'],'tagAlias'=>$this->attributes['slug']]);
    }
    
}
