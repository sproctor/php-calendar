<?php
/*
 * Copyright 2009 Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if(!defined('IN_PHPC')) {
       die("Hacking attempt");
}

function logout()
{
	global $vars, $day, $month, $year, $phpc_script;

        session_destroy();

	$string = "$phpc_script?";
        $arguments = array();
/* We might not have permission for the last action. We can probably
 * assume that the user is going away.
 * TODO: add a logout page here with a redirect to the front page of the
 *       calendar they were just viewing

        if(!empty($vars['lastaction']))
                $arguments[] = "action=$vars[lastaction]";
 */

        if(isset($vars['phpcid']))
                $arguments[] = "phpcid={$vars['phpcid']}";
        if(!empty($vars['year']))
                $arguments[] = "year=$year";
        if(!empty($vars['month']))
                $arguments[] = "month=$month";
        if(!empty($vars['day']))
                $arguments[] = "day=$day";
        redirect($string . implode('&', $arguments));

        return tag('h2', _('Loggin out...'));
}
?>
