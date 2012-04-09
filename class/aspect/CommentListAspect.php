<?php
namespace org\opencomb\oauthcommentadapter\aspect ;

use org\jecat\framework\lang\aop\jointpoint\JointPointMethodDefine;

class CommentListAspect
{
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