<?php namespace Boxkode\Forum\Resource;

use App;
use Config;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Boxkode\Forum\Repositories\Threads;
use Route;
use Validator;

class ThreadResource extends Controller {

	protected $threads;

	public function __construct(Threads $threads)
	{
		$this->threads = $threads;
	}

	/**
	 * GET Display showing data Tag
	 * @param integer
	 * @return Response (json)
	 */
	public function show($tagID)
	{
		return (string) $this->threads->getDetail($tagID);
	}

	/**
	 * GET Display all data tag
	 * @return Response (json)
	 */
	public function index()
	{
		return $this->threads->getAll()->toJson();
	}

	/**
	 * POST Store data tag
	 * @param Request
	 * @return Response (json)
	 */
	public function store(Request $request)
	{
		$data = [
			
		];
		
		$valid = Validator::make($data,[
			
		]);

		if($valid->fails()){
			return Response::json(['errors'=>$valid], 503);
		}else{
			$this->threads->create($data);
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
			$this->threads->update($data);
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
		$this->threads->delete($tagID);
		return Response::json([
			'errors'	=> false,
			'message'	=> trans('forum.tag-success-delete')
		]);
	}
} 