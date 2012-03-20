<?php
namespace org\opencomb\oauthcommentadapter\comment ;

use org\jecat\framework\lang\Object ;
use org\opencomb\oauth\adapter\AuthAdapterException;

class CommentFactory extends Object{
	/**
	 * @param $sService string 服务名字
	 *     如 weibo.com
	 * @return IComment
	 */
	public function create($sService){
		$sService = str_replace('.','_',$sService) ;
		
		$sClassName = __NAMESPACE__.'\\Comment_'.$sService;
		
		if( class_exists( $sClassName ) ){
			return new $sClassName ;
		}else{
			throw new AuthAdapterException('未实现的comment:%s',$sService);
			return null ;
		}
	}
}
