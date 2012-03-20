<?php
namespace org\opencomb\oauthcommentadapter\pull ;

/**
 * 这个对象应由 PullerFactory 产生
 */
interface IPuller{
	/**
	 * 一共有多少条评论
	 */
	public function commentCount();
	
	/**
	 * 拉取更古老的评论
	 * 
	 * @param $iFrom mix 根据不同网站，类型及含义不同。
	 *     如果为 null ， 则拉取最新评论。
	 * @param 拉取多少条
	 */
	public function pullOlder($iFrom,$iCount);
	
	/**
	 * 传入当前这次的 $iFrom ， 返回下一次的 $iFrom 。
	 * 根据不同网站，类型及含义不同。
	 * 返回的值，通常会传递给前台。
	 */
	public function getNextOlderFrom($iFrom);
}
