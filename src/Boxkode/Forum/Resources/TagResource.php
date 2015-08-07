<?php namespace Boxkode\Forum\Resources;

use App;
use Config;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Boxkode\Forum\Repositories\Tags;
use Route;
use Validator;

class TagResource extends Controller {

	protected $tags;

	public function __construct(Tags $tags)
	{
		$this->tags = $tags;
	}

	/**
	 * GET Display showing data Tag
	 * @param integer
	 * @return Response (json)
	 */
	public function show($tagID)
	{
		return (string) $this->tags->getDetail($tagID);
	}

	/**
	 * GET Display all data tag
	 * @return Response (json)
	 */
	public function index()
	{
		return $this->tags->getAll()->toJson();
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
			$this->tags->create($data);
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
			$this->tags->update($data);
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
		$this->tags->delete($tagID);
		return Response::json([
			'errors'	=> false,
			'message'	=> trans('forum.tag-success-delete')
		]);
	}
} 
