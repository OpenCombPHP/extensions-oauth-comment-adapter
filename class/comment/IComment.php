<?php
namespace org\opencomb\oauthcommentadapter\comment ;

/**
 * 这个对象应由 CommentFactory 产生
 */
interface IComment{
	/**
	 * @param $arr Puller对象得到的数据转化为数组后的值。
	 */
	public function setArray(array $arr) ;
	
	/**
	 * 测试用。
	 */
	public function toHtml() ;
	
	/**
	 * 用户名
	 */
	public function username() ;
	
	/**
	 * 用户头像
	 */
	public function avatar() ;
	
	/**
	 * 评论正文
	 */
	public function text() ;
	
	/**
	 * 该条评论在原网站上的id
	 */
	public function tcid() ;
	
	/**
	 * 该条评论的作者在原网站上的uid
	 */
	public function tuid() ;
	
	/**
	 * 评论时间
	 * @return int 返回timestamp
	 */
	public function create_time() ;
	
	/**
	 * 原网站名字
	 */
	public function service() ;
	
	/**
	 * 在原网站是否通过了认证
	 * @return boolean
	 */
	public function verified() ;
}
