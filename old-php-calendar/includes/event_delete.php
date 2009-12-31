<?php
/*
   Copyright 2006 Sean Proctor

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

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

function event_delete(&$calendar)
{
	if(false) {
		soft_error(_('You do not have permission to delete events.'));
	}

	$html = tag('div', attributes('class="box"', 'style="width: 50%"'));

	$delete = array();
	foreach($calendar->vars as $k => $v) {
		if(preg_match('/^delete(\d+)$/', $k, $matches) == 0
				|| $v != 'y')
			continue;
		$delete[] = $matches[1];
	}

	if(count($delete) == 0) {
		$html->add(tag('p', _('No items selected.')));
	}
	// else
	foreach($delete as $id) {
		if($calendar->db->delete_event($id)) {
			$html->add(tag('p', _('Deleted item') . ": $id"));
		} else {        
			$html->add(tag('p', _('Could not delete item')
						. ": $id"));
		}
	}

        return $html;
}

?>
