<?php
if (!isset($controller)) {
	throw new Exception ("This file can't be included outside of ForumServiceProvider@boot!");
}

Route::group(config('forum.routing.options'),function() use($controller){

	/*
	* Resources Group
	* 
	*/
	Route::group(['prefix'=>'resource'],function(){
		Route::resource('tag','Boxkode\Forum\Resources\TagResource');
		Route::resource('thread','Boxkode\Forum\Resources\ThreadResource');
		Route::resource('post','Boxkode\Forum\Resources\PostResource');	
	});


	Route::get('/', [
		'as'=>'home', 
		'uses'=>$controller . '@getViewIndex'
	]);

	Route::get('home', $controller . '@getViewIndex');
	
	Route::get('new', [
		'as' => 'forum.get.new', 
		'uses' => $controller . '@getViewNew'
	]);
	Route::post('new/read', [
		'as' => 'forum.post.mark.read', 
		'uses' => $controller . '@postMarkAsRead'
	]);

	Route::get('/tag/{tagID}/{tagAlias}', [
		'as' => 'forum.get.view.tag', 
		'uses' => $controller . '@getViewTag'
	]);
	

	Route::get('/thread/create', [
		'as' => 'forum.get.create.thread', 
		'uses' => $controller . '@getCreateThread'
	]);
	Route::post('/thread/create', [
		'as' => 'forum.post.create.thread', 
		'uses' => $controller . '@postCreateThread'
	]);



	$thread = '{threadID}/{threadAlias}';
	Route::get($thread, [
		'as' => 'forum.get.view.thread', 
		'uses' => $controller . '@getViewThread'
	]);

	Route::get($thread . '/reply', [
		'as' => 'forum.get.reply.thread', 
		'uses' => $controller . '@getReplyToThread'
	]);
	Route::post($thread . '/reply', [
		'as' => 'forum.post.reply.thread', 
		'uses' => $controller . '@postReplyToThread'
	]);

	Route::post($thread . '/lock', [
		'as' => 'forum.post.lock.thread', 
		'uses' => $controller . '@postLockThread'
	]);
	Route::delete($thread . '/delete', [
		'as' => 'forum.delete.thread', 
		'uses' => $controller . '@deleteThread'
	]);

	Route::get($thread . '/post/{postID}/edit', [
		'as' => 'forum.get.edit.post', 
		'uses' => $controller . '@getEditPost'
	]);
	Route::post($thread . '/post/{postID}/edit', [
		'as' => 'forum.post.edit.post', 
		'uses' => $controller . '@postEditPost'
	]);
	Route::delete($thread . '/post/{postID}/delete', [
		'as' => 'forum.get.delete.post', 
		'uses' => $controller . '@deletePost'
	]);

});