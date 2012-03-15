<?php 
namespace org\opencomb\oauthcommentadapter ;

use org\jecat\framework\lang\aop\AOP;
use org\opencomb\platform\ext\Extension ;

class OAuthCommentAdapter extends Extension 
{
	/**
	 * 载入扩展
	 */
	public function load()
	{
		//修改liststate中链接
		AOP::singleton()->register('org\\opencomb\\oauthcommentadapter\\aspect\\CommentListAspect') ;
	}
}