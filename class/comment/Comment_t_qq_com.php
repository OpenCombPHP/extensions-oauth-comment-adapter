<?php
namespace org\opencomb\oauthcommentadapter\comment ;

class Comment_t_qq_com extends AbstractComment{
	protected function img(){
		return $this->arr['head'].'/50' ;
	}
	
	protected function name(){
		return $this->arr['nick'] ;
	}
	
	protected function time(){
		$iTime = $this->arr['timestamp'] ;
		return date('Y-m-d H:i');
	}
	
	public function username(){
		return $this->name() ;
	}
	
	public function avatar(){
		return $this->img() ;
	}
	
	public function create_time(){
		return $this->arr['timestamp'] ;
	}
	
	public function verified(){
		return $this->arr['isvip'] ;
	}
}
