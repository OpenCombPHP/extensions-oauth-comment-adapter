<?php
namespace org\opencomb\oauthcommentadapter\comment ;

class Comment_163_com extends AbstractComment{
	public function username(){
		return $this->arr['user']['screen_name'] ;
	}
	
	public function avatar(){
		return $this->arr['user']['profile_image_url'] ;
	}
	
	public function create_time(){
		$sTime = $this->arr['created_at'] ;
		
		return strtotime($sTime);
	}
}
