<?php namespace Boxkode\Forum\Resources;

use App;
use Config;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Boxkode\Forum\Repositories\Posts;
use Route;
use Validator;

class PostResource extends Controller {

	protected $posts;

	public function __construct(Posts $posts)
	{
		$this->posts = $posts;
	}

	/**
	 * GET Display showing data Tag
	 * @param integer
	 * @return Response (json)
	 */
	public function show($tagID)
	{
		return (string) $this->posts->getDetail($tagID);
	}

	/**
	 * GET Display all data tag
	 * @return Response (json)
	 */
	public function index()
	{
		return $this->posts->getAll()->toJson();
	}

	/**
	 * POST Store data tag
	 * @param Request
	 * @return Response (json)
	 */
	public function store(Request $request)
	{
		$data = [
			'title'		=> $request->input('title'),
			'slug'		=> str_slug($request->input('title')),
			'content'	=> $request->input('content'),
			'image'		=> $request->input('image'),
		];
		
		$valid = Validator::make($data,[
			'title'		=> 'required|max:255',
			'content'	=> 'required',
		]);

		if($valid->fails()){
			return Response::json(['errors'=>$valid], 503);
		}else{
			$this->posts->create($data);
			return Response::json([
				'errors'	=> false,
				'message'	=> trans('forum.tag-success-create')
			]);
		}

	}

	/**
	 * PUT/PATCH Updating data tag
	 * @return Response (json)
	 */
	public function update(Request $request,$tagID)
	{
		$data = [
			'title'		=> $request->input('title'),
			'slug'		=> str_slug($request->input('title')),
			'content'	=> $request->input('content'),
			'image'		=> $request->input('image'),
			'id'		=> $tagID
		];
		
		$valid = Validator::make($data,[
			'title'		=> 'required|max:255',
			'content'	=> 'required',
		]);

		if($valid->fails()){
			return Response::json(['errors'=>$valid], 503);
		}else{
			$this->posts->update($data);
			return Response::json([
				'errors'	=> false,
				'message'	=> trans('forum.tag-success-update')
			]);
		}
	}

	/**
	 * DELETE deleting data tag softly
	 * @return Response (json)
	 */
	public function destroy($tagID)
	{
		$this->posts->delete($tagID);
		return Response::json([
			'errors'	=> false,
			'message'	=> trans('forum.tag-success-delete')
		]);
	}
} 