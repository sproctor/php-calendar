<?php 
/*
   Copyright 2002 Sean Proctor, Nathan Poiro

   This file is part of PHP-Calendar.

   PHP-Calendar is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-Calendar is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-Calendar; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

function new_user_submit()
{
	global $calendar_name, $vars, $db;

        if(!check_user()) {
                return tag('div', _('Permission denied'));
        }

        if($vars['password1'] != $vars['password2']) {
                return tag('div', _('You passwords did not match'));
        }

        $passwd = md5($_POST['password1']);

        /* start the UIDs at 2 to be backward compatible with all calendars
           that don't have a uid sequence */
        $query = "insert into ".SQL_PREFIX."users\n"
                ."(uid, username, password, calendar) VALUES\n"
                ."('".$db->GenID('uid', 2)."', '$_POST[user_name]', '$passwd',"
                ." $calendar_name)";

        $result = $db->Execute($query)
                or db_error(_('Error creating user'), $query);

        return tag('div', _('Added user'));
}

?>
