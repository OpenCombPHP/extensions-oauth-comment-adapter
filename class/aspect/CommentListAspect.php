<?php
namespace org\opencomb\oauthcommentadapter\aspect ;

use org\jecat\framework\message\Message;
use org\jecat\framework\bean\BeanFactory;
use org\jecat\framework\mvc\model\db\Model;
use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class CommentListAspect
{
	/**
	 * @pointcut
	 */
	public function pointcutCreateBeanConfig()
	{
		return array(
			new JointPointMethodDefine('org\\opencomb\\comment\\CommentList','createBeanConfig') ,
		) ;
	}
	
	/**
	 * @advice around
	 * @for pointcutCreateBeanConfig
	 */
	private function createBeanConfig()
	{
		$arrConfig = aop_call_origin() ;
		$arrConfig['controllers']['pullComment'] = array(
						'class'=>'org\opencomb\oauthcommentadapter\PullComment',
				);
		return $arrConfig;
	}
}
?>