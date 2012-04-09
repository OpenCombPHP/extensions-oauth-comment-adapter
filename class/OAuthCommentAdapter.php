<?php
namespace org\opencomb\oauthcommentadapter;

use org\jecat\framework\lang\aop\AOP;
use org\opencomb\platform\ext\Extension;

class OAuthCommentAdapter extends Extension {
	/**
	 * 载入扩展
	 */
	public function load() {
		AOP::singleton ()->registerBean ( array (
				// jointpoint
				'org\\opencomb\\comment\\CommentList::createBeanConfig()',
				// advice
				array (
						'org\\opencomb\\oauthcommentadapter\\aspect\\CommentListAspect', // 修改liststate中链接
						'createBeanConfig' 
				) 
		), __FILE__ )->registerBean ( array (
				// jointpoint
				'org\\opencomb\\comment\\CreateComment::process()',
				// advice
				array (
						'org\\opencomb\\oauthcommentadapter\\aspect\\CreateCommentAspect', // 发布消息同步到weibo
						'process'
				)
		), __FILE__ );
	}
}