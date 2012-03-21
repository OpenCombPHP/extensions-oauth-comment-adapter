<?php
namespace org\opencomb\oauthcommentadapter\comment ;

class Comment_renren_com extends AbstractComment{
	public function username(){
		return $this->arr['name'] ;
	}
	
	public function avatar(){
		return $this->arr['tinyurl'] ;
	}
	
	public function create_time(){
		$sTime = $this->arr['time'] ;
		
		return strtotime($sTime);
	}
	
	public function tcid(){
		return $this->arr['comment_id'] ;
	}
	
	public function tuid(){
		return $this->arr['uid'];
	}
}
