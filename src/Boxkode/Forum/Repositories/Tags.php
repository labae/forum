<?php namespace Boxkode\Forum\Repositories;

use Boxkode\Forum\Models\Tag;

class Tags extends BaseRepository {

	public function __construct(Tag $model)
	{
		$this->model = $model;
	}

	public function getAll($where = array())
	{
		return $this->model->where($where)->get();
	}

	public function getDetail($tagID)
	{
		return $this->model->find($tagID);
	}

}