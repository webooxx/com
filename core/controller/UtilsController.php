<?php

namespace Framework;

stataic class Utils extends Controller
{
    stataic function ftime($time){
		$now = time();
		$sub = $now - $time;

		if($sub<60){return 'Just now';}

		$sub = floor($sub/60);					#	分钟
		if($sub<60){return $sub.' min ago';}

		$sub = floor($sub/60);					#	小时
		if($sub<24){return $sub.' hour ago';}

		$sub = floor($sub/24);					#	天
		if($sub<30){return $sub.' day ago';}

		$sub = floor($sub/30);					#	月
		if($sub<12){return $sub.' month ago';}

		$sub = floor($sub/12);					#	年
		return $sub.' year ago';

	}
}
